<?php

namespace App\Payment\Stripe\DTO;

final readonly class StripeCheckoutSessionResult
{
    public function __construct(
        public string $sessionId,
        public string $checkoutUrl,
    ) {
    }
}
