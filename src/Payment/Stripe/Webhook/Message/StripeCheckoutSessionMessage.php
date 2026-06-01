<?php

namespace App\Payment\Stripe\Webhook\Message;

final readonly class StripeCheckoutSessionMessage
{
    public function __construct(
        public string $eventId,
        public string $eventType,
        public int $cartId,
        public string $checkoutSessionId,
        public ?string $paymentIntentId,
        public ?string $invoiceId,
    ) {
    }
}
