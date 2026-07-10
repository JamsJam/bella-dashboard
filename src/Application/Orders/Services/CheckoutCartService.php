<?php

namespace App\Application\Orders\Services;

use App\ApiResource\Orders\CheckoutCartInput;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Orders\Cart;
use App\Entity\Orders\CartItem;
use App\Entity\Users\Customers;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CheckoutCartService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function createPendingCart(CheckoutCartInput $input, Customers $customer): Cart
    {
        $cart = (new Cart())
            ->setCustomer($customer)
            ->setCurrency($input->currency)
            ->setStatus(Cart::STATUS_PENDING);

        foreach ($input->items as $itemPayload) {
            $item = $this->createItem($itemPayload);
            $cart->addItem($item);
        }

        if ($cart->getItems()->isEmpty()) {
            throw new \InvalidArgumentException('Cart must contain at least one item.');
        }

        $cart->recalculateTotals();

        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        return $cart;
    }

    private function createItem(array $payload): CartItem
    {
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

        $variant = $this->entityManager->getRepository(ClothesVariant::class)->findOneWithProduct($variantId);
        $clothe = $variant?->getClothes();

        if (!$variant instanceof ClothesVariant || !$clothe?->isOnline() || !$variant->isOnline() || $variant->getStock() < $quantity) {
            throw new \InvalidArgumentException('Selected variant is unavailable.');
        }

        $unitPriceTTC = (int) $clothe->getPrice();
        if ($unitPriceTTC <= 0) {
            throw new \InvalidArgumentException('Selected product has no valid price.');
        }

        return (new CartItem())
            ->setVariant($variant)
            ->setProductId($variantId)
            ->setName($variant->getDisplayName())
            ->setQuantity($quantity)
            ->setUnitPriceTTC($unitPriceTTC);
    }
}
