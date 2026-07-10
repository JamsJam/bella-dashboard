<?php

namespace App\Payment\Stripe\Factory;

use App\Entity\Orders\Cart;

final readonly class StripeCheckoutLineItemsFactory
{
    public function createFromCart(Cart $cart): array
    {
        $lineItems = [];

        foreach ($cart->getItems() as $item) {
            $lineItems[] = [
                'quantity' => $item->getQuantity(),
                'price_data' => [
                    'currency' => $cart->getCurrency(),
                    'unit_amount' => $item->getUnitPriceTTC(),
                    'product_data' => [
                        'name' => $item->getName(),
                        'metadata' => [
                            'product_id' => (string) $item->getProductId(),
                            'variant_id' => (string) ($item->getVariant()?->getId() ?? $item->getProductId()),
                        ],
                    ],
                ],
            ];
        }

        return $lineItems;
    }
}
