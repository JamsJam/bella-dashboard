<?php

namespace App\Application\Config\ConfigModel;

final readonly class OrderConfigModel
{
    public function __construct(
        public string $currency = 'EUR',
        public string $defaultStatus = 'pending',
        public int $minimumAmount = 0,
        public int $shippingCost = 0,
        public ?int $freeShippingFrom = null,
        public bool $allowGuestCheckout = false,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            currency: strtoupper(trim((string) ($data['currency'] ?? 'EUR'))),
            defaultStatus: trim((string) ($data['default_status'] ?? 'pending')),
            minimumAmount: max(0, (int) ($data['minimum_amount'] ?? 0)),
            shippingCost: max(0, (int) ($data['shipping_cost'] ?? 0)),
            freeShippingFrom: isset($data['free_shipping_from']) ? max(0, (int) $data['free_shipping_from']) : null,
            allowGuestCheckout: (bool) ($data['allow_guest_checkout'] ?? false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'currency' => $this->currency,
            'default_status' => $this->defaultStatus,
            'minimum_amount' => $this->minimumAmount,
            'shipping_cost' => $this->shippingCost,
            'free_shipping_from' => $this->freeShippingFrom,
            'allow_guest_checkout' => $this->allowGuestCheckout,
        ];
    }
}
