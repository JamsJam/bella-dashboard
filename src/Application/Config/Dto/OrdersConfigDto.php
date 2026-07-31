<?php

namespace App\Application\Config\Dto;

final class OrdersConfigDto
{
    /**
     * @param array<int, ShippingFeeDto> $shippingFees
     * @param array<int, CarrierDto> $carriers
     */
    public function __construct(
        public float $vat = 20.0,
        public array $shippingFees = [],
        public array $carriers = [],
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

        $carriers = [];
        foreach (($data['carriers'] ?? []) as $carrier) {
            if (is_array($carrier)) {
                $carriers[] = CarrierDto::fromArray($carrier);
            }
        }
        if ($carriers === []) {
            $carriers = [
                new CarrierDto('La Poste', 'https://www.laposte.fr/outils/suivre-vos-envois?code='),
                new CarrierDto('Colissimo', 'https://www.laposte.fr/outils/suivre-vos-envois?code='),
                new CarrierDto('DPD', 'https://trace.dpd.fr/fr/trace/'),
            ];
        }

        return new self(
            vat: max(0.0, (float) ($data['vat'] ?? 20.0)),
            shippingFees: $shippingFees,
            carriers: $carriers,
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
            'carriers' => array_map(
                static fn (CarrierDto $carrier): array => $carrier->toArray(),
                $this->carriers,
            ),
        ];
    }
}
