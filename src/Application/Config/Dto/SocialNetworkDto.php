<?php

namespace App\Application\Config\Dto;

final class SocialNetworkDto
{
    public function __construct(
        public string $name = '',
        public string $url = '',
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: trim((string) ($data['name'] ?? '')),
            url: trim((string) ($data['url'] ?? '')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->name !== '') {
            $data['name'] = $this->name;
        }

        if ($this->url !== '') {
            $data['url'] = $this->url;
        }

        return $data;
    }
}
