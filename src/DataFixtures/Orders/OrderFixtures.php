<?php

namespace App\DataFixtures\Orders;

use App\DataFixtures\AbstractBaseFixtures;
use App\DataFixtures\FixtureReferences;
use App\DataFixtures\Users\CustomerFixtures;
use App\Entity\Orders\Cart;
use App\Entity\Orders\CartItem;
use App\Entity\Orders\Orders;
use App\Entity\Users\Customers;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class OrderFixtures extends AbstractBaseFixtures implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($customerIndex = 0; $customerIndex < 12; $customerIndex++) {
            /** @var Customers $customer */
            $customer = $this->getReference(FixtureReferences::CUSTOMERS.$customerIndex, Customers::class);

            $cart = (new Cart())
                ->setCustomer($customer)
                ->setCurrency('eur')
                ->setStatus($customerIndex % 3 === 0 ? Cart::STATUS_PENDING : Cart::STATUS_PAID);

            for ($i = 0; $i < $this->faker->numberBetween(1, 4); $i++) {
                $cart->addItem($this->createCartItem());
            }

            $cart->recalculateTotals();
            $this->persistTouched($manager, $cart);

            if ($cart->getStatus() !== Cart::STATUS_PAID) {
                continue;
            }

            $order = (new Orders())
                ->setCart($cart)
                ->setCustomer($customer)
                ->setSubtotal($cart->getSubtotal())
                ->setTotal($cart->getTotal())
                ->setStatus($this->faker->randomElement(['paid', 'processing', 'shipped']))
                ->setOrderReference(sprintf('ORDER-%s-%06d', (new \DateTimeImmutable())->format('Ymd'), $customerIndex + 1))
                ->setFees(0)
                ->setShippinfo([
                    'city' => $this->faker->city(),
                    'postcode' => $this->faker->postcode(),
                    'address' => $this->faker->streetAddress(),
                ])
                ->setTva(0);

            $this->persistTouched($manager, $order);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [CustomerFixtures::class];
    }

    private function createCartItem(): CartItem
    {
        $item = (new CartItem())
            ->setProductId($this->faker->numberBetween(1, 60))
            ->setName($this->faker->randomElement(['T-shirt', 'Hoodie', 'Casquette', 'Sticker', 'Affiche']))
            ->setQuantity($this->faker->numberBetween(1, 3))
            ->setUnitPriceTTC($this->faker->randomElement([990, 1490, 2990, 4990, 7990]));

        $this->touch($item);

        return $item;
    }
}
