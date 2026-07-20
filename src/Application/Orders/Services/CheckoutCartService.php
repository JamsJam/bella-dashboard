<?php

namespace App\Application\Orders\Services;

use App\ApiResource\Orders\CheckoutCartInput;
use App\Application\Config\Dto\ShippingFeeDto;
use App\Application\Config\Provider\OrdersConfigProvider;
use App\Application\Orders\Exception\InsufficientVariantStockException;
use App\Application\Orders\Exception\InvalidShippingDestinationException;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Orders\Cart;
use App\Entity\Orders\CartItem;
use App\Entity\Orders\Orders;
use App\Entity\Users\Customers;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CheckoutCartService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrdersConfigProvider $ordersConfigProvider,
    ) {
    }

    public function createPendingOrder(CheckoutCartInput $input, Customers $customer): Orders
    {
        $quantitiesByVariant = $this->aggregateItems($input->items);
        $shippingFee = $this->resolveShippingFee($input->shippingDestination);

        return $this->entityManager->wrapInTransaction(function () use ($input, $customer, $quantitiesByVariant, $shippingFee): Orders {
            $cart = (new Cart())
                ->setCustomer($customer)
                ->setCurrency($input->currency);

            foreach ($quantitiesByVariant as $variantId => $quantity) {
                $item = $this->createAndReserveItem($variantId, $quantity);
                $cart->addItem($item);
            }

            $cart->recalculateTotals();
            $this->entityManager->persist($cart);
            $this->entityManager->flush();

            $order = (new Orders())
                ->setCart($cart)
                ->setCustomer($customer)
                ->setSubtotal($cart->getSubtotal())
                ->setTotal($cart->getTotal() + $shippingFee->priceCents)
                ->setStatus(Orders::STATUS_PENDING_PAYMENT)
                ->setOrderReference($this->createOrderReference($cart))
                ->setFees($shippingFee->priceCents)
                ->setShippinfo(['destination' => $shippingFee->destination])
                ->setTva(0);

            $this->entityManager->persist($order);
            $this->entityManager->flush();

            return $order;
        });
    }

    private function resolveShippingFee(string $destination): ShippingFeeDto
    {
        $normalizedDestination = mb_strtolower(trim($destination));

        foreach ($this->ordersConfigProvider->get()->shippingFees as $shippingFee) {
            if (
                $shippingFee instanceof ShippingFeeDto
                && mb_strtolower(trim($shippingFee->destination)) === $normalizedDestination
            ) {
                return $shippingFee;
            }
        }

        throw new InvalidShippingDestinationException($destination);
    }

    /**
     * @param array<int, array{variantId: int, quantity: int}> $items
     *
     * @return array<int, int>
     */
    private function aggregateItems(array $items): array
    {
        $quantitiesByVariant = [];

        foreach ($items as $payload) {
            foreach (['variantId', 'quantity'] as $field) {
                if (!array_key_exists($field, $payload)) {
                    throw new \InvalidArgumentException(sprintf('Missing cart item field "%s".', $field));
                }
            }

            $variantId = filter_var($payload['variantId'], FILTER_VALIDATE_INT);
            $quantity = filter_var($payload['quantity'], FILTER_VALIDATE_INT);

            if ($variantId === false || $variantId <= 0 || $quantity === false || $quantity <= 0) {
                throw new \InvalidArgumentException('Invalid cart item payload.');
            }

            $quantitiesByVariant[$variantId] = ($quantitiesByVariant[$variantId] ?? 0) + $quantity;
        }

        if ($quantitiesByVariant === []) {
            throw new \InvalidArgumentException('Cart must contain at least one item.');
        }

        ksort($quantitiesByVariant);

        return $quantitiesByVariant;
    }

    private function createAndReserveItem(int $variantId, int $quantity): CartItem
    {
        $variant = $this->entityManager->getRepository(ClothesVariant::class)->findOneWithProductForUpdate($variantId);
        $clothe = $variant?->getClothes();

        if (!$variant instanceof ClothesVariant || !$clothe?->isOnline() || !$variant->isOnline()) {
            throw new \InvalidArgumentException('Selected variant is unavailable.');
        }

        if ($variant->getStock() < $quantity) {
            throw new InsufficientVariantStockException($variantId, $quantity, $variant->getStock());
        }

        $unitPriceTTC = (int) $clothe->getPrice();
        if ($unitPriceTTC <= 0) {
            throw new \InvalidArgumentException('Selected product has no valid price.');
        }

        $variant
            ->setStock($variant->getStock() - $quantity)
            ->setEditedAt(new \DateTimeImmutable());

        return (new CartItem())
            ->setVariant($variant)
            ->setProductId($variantId)
            ->setName($variant->getDisplayName())
            ->setQuantity($quantity)
            ->setUnitPriceTTC($unitPriceTTC);
    }

    public function releaseReservation(Orders $order, string $status): void
    {
        $orderId = $order->getId();
        if ($orderId === null) {
            return;
        }

        $this->entityManager->wrapInTransaction(function () use ($orderId, $status): void {
            $lockedOrder = $this->entityManager->getRepository(Orders::class)->findForUpdate($orderId);
            if (!$lockedOrder instanceof Orders || $lockedOrder->getStatus() !== Orders::STATUS_PENDING_PAYMENT) {
                return;
            }

            foreach ($lockedOrder->getCart()?->getItems() ?? [] as $item) {
                $variantId = $item->getVariant()?->getId();
                if ($variantId === null) {
                    continue;
                }

                $variant = $this->entityManager->getRepository(ClothesVariant::class)->findOneWithProductForUpdate($variantId);
                if (!$variant instanceof ClothesVariant) {
                    continue;
                }

                $variant
                    ->setStock($variant->getStock() + (int) $item->getQuantity())
                    ->setEditedAt(new \DateTimeImmutable());
            }

            $lockedOrder->setStatus($status);
            $this->entityManager->flush();
        });
    }

    private function createOrderReference(Cart $cart): string
    {
        return sprintf('ORDER-%s-%06d', (new \DateTimeImmutable())->format('Ymd'), (int) $cart->getId());
    }
}
