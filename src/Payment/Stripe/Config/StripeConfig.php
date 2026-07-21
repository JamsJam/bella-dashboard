<?php

namespace App\Payment\Stripe\Config;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class StripeConfig
{
    public function __construct(
        #[Autowire('%kernel.environment%')]
        private string $environment,
        #[Autowire('%env(STRIPE_SECRET_KEY_DEV)%')]
        private string $secretKeyDev,
        #[Autowire('%env(STRIPE_SECRET_KEY_PROD)%')]
        private string $secretKeyProd,
        #[Autowire('%env(STRIPE_WEBHOOK_SECRET_DEV)%')]
        private string $webhookSecretDev,
        #[Autowire('%env(STRIPE_WEBHOOK_SECRET_PROD)%')]
        private string $webhookSecretProd,
        #[Autowire('%env(STRIPE_SUCCESS)%')]
        private string $successUrl,
        #[Autowire('%env(FRONT_APP_URL)%')]
        private string $frontAppUrl,
    ) {
    }

    public function getSecretKey(): string
    {
        return $this->isProd() ? $this->secretKeyProd : $this->secretKeyDev;
    }

    public function getWebhookSecret(): string
    {
        return $this->isProd() ? $this->webhookSecretProd : $this->webhookSecretDev;
    }

    public function getSuccessUrl(): string
    {
        return $this->successUrl;
    }

    public function getCancelUrl(): string
    {
        return rtrim($this->frontAppUrl, '/').'/payment/cancel';
    }

    public function getCartUrl(): string
    {
        return rtrim($this->frontAppUrl, '/').'/cart';
    }

    private function isProd(): bool
    {
        return $this->environment === 'prod';
    }
}
