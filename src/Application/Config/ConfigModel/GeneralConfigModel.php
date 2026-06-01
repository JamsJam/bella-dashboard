<?php

namespace App\Application\Config\ConfigModel;

final readonly class GeneralConfigModel
{
    public function __construct(
        public string $email = 'contact@example.com',
        public string $address = '',
        public string $phone = '',
        public string $siteTitle = 'Bella GP',
        public string $siteDescription = '',
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: trim((string) ($data['email'] ?? 'contact@example.com')),
            address: trim((string) ($data['address'] ?? '')),
            phone: trim((string) ($data['phone'] ?? '')),
            siteTitle: trim((string) ($data['site_title'] ?? 'Bella GP')),
            siteDescription: trim((string) ($data['site_description'] ?? '')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'address' => $this->address,
            'phone' => $this->phone,
            'site_title' => $this->siteTitle,
            'site_description' => $this->siteDescription,
        ];
    }
}
