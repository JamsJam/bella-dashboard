<?php

namespace App\DataFixtures\Orders;

use App\Application\Orders\Services\OrderReferenceGenerator;
use App\DataFixtures\AbstractBaseFixtures;
use App\DataFixtures\Clothes\ClothesFixtures;
use App\DataFixtures\FixtureReferences;
use App\DataFixtures\Users\CustomerFixtures;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Orders\Cart;
use App\Entity\Orders\CartItem;
use App\Entity\Orders\Orders;
use App\Entity\Users\Customers;
use App\Enum\OrderStatus;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class OrderFixtures extends AbstractBaseFixtures implements DependentFixtureInterface, FixtureGroupInterface
{
    private const ORDER_COUNT = 1000;
    private const CUSTOMER_COUNT = 20;
    private const VARIANT_COUNT = 80;
    private const SHIPPING_FEE = 890;

    public function __construct(
        private readonly OrderReferenceGenerator $orderReferenceGenerator,
    ) {
        parent::__construct();
    }

    public function load(ObjectManager $manager): void
    {
        $generatedReferences = [];

        for ($orderIndex = 0; $orderIndex < self::ORDER_COUNT; ++$orderIndex) {
            /** @var Customers $customer */
            $customer = $this->getReference(
                FixtureReferences::CUSTOMERS.($orderIndex % self::CUSTOMER_COUNT),
                Customers::class,
            );

            $cart = (new Cart())
                ->setCustomer($customer)
                ->setCurrency('eur');

            for ($itemIndex = 0; $itemIndex < $this->faker->numberBetween(1, 4); ++$itemIndex) {
                $cart->addItem($this->createCartItem());
            }

            $cart->recalculateTotals();
            $this->persistTouched($manager, $cart);

            $orderStatus = $this->orderStatus($orderIndex);
            $outsideGuadeloupe = $orderStatus === OrderStatus::Shipped
                || ($orderStatus === OrderStatus::Processing && intdiv($orderIndex, 6) % 2 === 1)
                || ($orderStatus === OrderStatus::Delivered && intdiv($orderIndex, 6) % 2 === 1);
            $order = (new Orders())
                ->setCart($cart)
                ->setCustomer($customer)
                ->setSubtotal($cart->getSubtotal())
                ->setTotal($cart->getTotal() + self::SHIPPING_FEE)
                ->setStatus($this->paymentStatus($orderStatus))
                ->setOrderStatus($orderStatus)
                ->setOrderReference($this->generateUniqueReference($generatedReferences))
                ->setFees(self::SHIPPING_FEE)
                ->setShippinfo([
                    'destination' => $outsideGuadeloupe ? 'France hexagonale' : 'Guadeloupe',
                    'firstName' => $this->faker->firstName(),
                    'lastName' => $this->faker->lastName(),
                    'phone' => $this->faker->phoneNumber(),
                    'city' => $this->faker->randomElement([
                        'Les Abymes',
                        'Baie-Mahault',
                        'Le Gosier',
                        'Sainte-Anne',
                        'Basse-Terre',
                    ]),
                    'postcode' => $this->faker->randomElement(['97110', '97122', '97139', '97180', '97190']),
                    'address' => $this->faker->streetAddress(),
                ])
                ->setTva((int) round($cart->getSubtotal() * 8.5 / 108.5));

            if (in_array($orderStatus, [OrderStatus::Processing, OrderStatus::AwaitingDelivery, OrderStatus::Shipped, OrderStatus::Delivered], true)) {
                $order
                    ->setStripeCheckoutSessionId(sprintf('cs_test_fixture_%02d', $orderIndex + 1))
                    ->setStripePaymentIntentId(sprintf('pi_test_fixture_%02d', $orderIndex + 1))
                    ->setStripeInvoiceId(sprintf('in_test_fixture_%02d', $orderIndex + 1))
                    ->setStripeInvoiceUrl(sprintf('https://invoice.example.test/%02d', $orderIndex + 1));
            }

            if ($orderStatus === OrderStatus::AwaitingDelivery) {
                $order->setDeliveryDate(
                    (new \DateTimeImmutable('today', new \DateTimeZone('Europe/Paris')))
                        ->modify(sprintf('+%d days', 1 + (intdiv($orderIndex, 5) % 5))),
                );
            }

            if ($orderStatus === OrderStatus::Delivered) {
                $deliveredAt = (new \DateTimeImmutable('today', new \DateTimeZone('Europe/Paris')))
                    ->modify(sprintf('-%d days', 1 + (intdiv($orderIndex, 6) % 10)));
                $order->setDeliveredAt($deliveredAt);

                if (!$outsideGuadeloupe) {
                    $order->setDeliveryDate($deliveredAt);
                }
            }

            if ($orderStatus === OrderStatus::Shipped || ($orderStatus === OrderStatus::Delivered && $outsideGuadeloupe)) {
                $daysAgo = $orderStatus === OrderStatus::Delivered
                    ? 21 + (intdiv($orderIndex, 6) % 10)
                    : intdiv($orderIndex, 6) % 10;
                $order
                    ->setTrackingNumber(sprintf('TRACK%08d', $orderIndex + 1))
                    ->setShippedAt(
                        (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))
                            ->modify(sprintf('-%d days', $daysAgo)),
                    );
            }

            $this->persistTouched($manager, $order);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CustomerFixtures::class,
            ClothesFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['commerce-test'];
    }

    private function createCartItem(): CartItem
    {
        /** @var ClothesVariant $variant */
        $variant = $this->getReference(
            FixtureReferences::CLOTHES_VARIANTS.$this->faker->numberBetween(0, self::VARIANT_COUNT - 1),
            ClothesVariant::class,
        );

        $item = (new CartItem())
            ->setVariant($variant)
            ->setProductId((int) $variant->getId())
            ->setName($variant->getDisplayName())
            ->setQuantity($this->faker->numberBetween(1, 3))
            ->setUnitPriceTTC((int) $variant->getClothes()?->getPrice());

        $this->touch($item);

        return $item;
    }

    private function orderStatus(int $orderIndex): OrderStatus
    {
        return match ($orderIndex % 6) {
            0 => OrderStatus::Created,
            1 => OrderStatus::Processing,
            2 => OrderStatus::Cancelled,
            3 => OrderStatus::AwaitingDelivery,
            4 => OrderStatus::Shipped,
            5 => OrderStatus::Delivered,
        };
    }

    private function paymentStatus(OrderStatus $orderStatus): string
    {
        return match ($orderStatus) {
            OrderStatus::Created => Orders::STATUS_PENDING_PAYMENT,
            OrderStatus::Cancelled => Orders::STATUS_PAYMENT_EXPIRED,
            OrderStatus::Processing, OrderStatus::AwaitingDelivery, OrderStatus::Shipped, OrderStatus::Delivered => Orders::STATUS_PAID,
        };
    }

    /**
     * @param array<string, true> $generatedReferences
     */
    private function generateUniqueReference(array &$generatedReferences): string
    {
        do {
            $reference = $this->orderReferenceGenerator->generate();
        } while (isset($generatedReferences[$reference]));

        $generatedReferences[$reference] = true;

        return $reference;
    }
}
