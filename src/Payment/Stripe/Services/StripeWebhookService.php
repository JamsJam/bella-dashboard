<?php

namespace App\Payment\Stripe\Services;

use App\Payment\Stripe\Webhook\StripeWebhookEventDispatcher;

final readonly class StripeWebhookService
{
    public function __construct(
        private StripeWebhookEventDispatcher $eventDispatcher,
    ) {
    }

    public function handle(string $payload, string $signature): void
    {
        $this->eventDispatcher->dispatchFromPayload($payload, $signature);
    }
}
