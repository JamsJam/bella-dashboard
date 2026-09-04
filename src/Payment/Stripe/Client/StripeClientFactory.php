<?php

namespace App\Payment\Stripe\Client;

use App\Payment\Stripe\Config\StripeConfig;
use Stripe\StripeClient;

final readonly class StripeClientFactory
{
    public function __construct(
        private StripeConfig $stripeConfig,
    ) {
    }

    public function create(): StripeClient
    {
        return new StripeClient($this->stripeConfig->getSecretKey());
    }
}
