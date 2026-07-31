<?php

namespace App\Application\Config\Dto;

final class CarrierDto
{
    public function __construct(
        public string $name = '',
        public string $trackingUrl = '',
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: trim((string) ($data['name'] ?? '')),
            trackingUrl: trim((string) ($data['tracking_url'] ?? '')),
        );
    }

    /** @return array{name: string, tracking_url: string} */
    public function toArray(): array
    {
        return ['name' => $this->name, 'tracking_url' => $this->trackingUrl];
    }
}
