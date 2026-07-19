<?php

namespace App\Payment\Stripe\Webhook\Message;

final readonly class StripeCheckoutSessionMessage
{
    public function __construct(
        public string $eventId,
        public string $eventType,
        public int $orderId,
        public string $checkoutSessionId,
        public string $paymentStatus,
        public ?string $paymentIntentId,
        public ?string $invoiceId,
    ) {
    }
}
