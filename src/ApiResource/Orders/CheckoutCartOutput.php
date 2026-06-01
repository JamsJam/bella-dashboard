<?php

namespace App\ApiResource\Orders;

final readonly class CheckoutCartOutput
{
    public function __construct(
        public int $cartId,
        public string $checkoutSessionId,
        public string $checkoutUrl,
    ) {
    }
}
