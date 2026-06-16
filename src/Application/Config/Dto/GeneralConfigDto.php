<?php

namespace App\Application\Config\Dto;

final class GeneralConfigDto
{
    public function __construct(
        public string $siteTitle = 'Bella GP',
        public string $siteLogo = '',
        public string $favicon = '',
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            siteTitle: trim((string) ($data['site_title'] ?? '')),
            siteLogo: trim((string) ($data['site_logo'] ?? '')),
            favicon: trim((string) ($data['favicon'] ?? '')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'site_title' => $this->siteTitle,
            'site_logo' => $this->siteLogo,
            'favicon' => $this->favicon,
        ];
    }
}
