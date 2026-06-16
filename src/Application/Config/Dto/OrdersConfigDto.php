<?php

namespace App\Application\Config\Dto;

final class OrdersConfigDto
{
    /**
     * @param array<int, ShippingFeeDto> $shippingFees
     */
    public function __construct(
        public float $vat = 20.0,
        public array $shippingFees = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $shippingFees = [];
        foreach (($data['shipping_fees'] ?? []) as $shippingFee) {
            if (is_array($shippingFee)) {
                $shippingFees[] = ShippingFeeDto::fromArray($shippingFee);
            }
        }

        if ($shippingFees === []) {
            $shippingFees[] = new ShippingFeeDto('France', 'FR', 0);
        }

        return new self(
            vat: max(0.0, (float) ($data['vat'] ?? 20.0)),
            shippingFees: $shippingFees,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'vat' => $this->vat,
            'shipping_fees' => array_map(
                static fn (ShippingFeeDto $shippingFee): array => $shippingFee->toArray(),
                $this->shippingFees,
            ),
        ];
    }
}
