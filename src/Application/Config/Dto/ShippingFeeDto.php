<?php

namespace App\Application\Config\Dto;

final class ShippingFeeDto
{
    public function __construct(
        public string $destination = '',
        public ?string $flag = null,
        public int $priceCents = 0,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            destination: trim((string) ($data['destination'] ?? '')),
            flag: trim((string) ($data['flag'] ?? '')) ?: null,
            priceCents: max(0, (int) ($data['price_cents'] ?? 0)),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'destination' => $this->destination,
            'flag' => $this->flag,
            'price_cents' => $this->priceCents,
        ];
    }
}
