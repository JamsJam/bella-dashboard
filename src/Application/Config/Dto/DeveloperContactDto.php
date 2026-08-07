<?php

namespace App\Application\Config\Dto;

final class DeveloperContactDto
{
    public function __construct(
        public string $email = '',
        public string $name = '',
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: trim((string) ($data['email'] ?? '')),
            name: trim((string) ($data['name'] ?? '')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ('' !== $this->email) {
            $data['email'] = $this->email;
        }

        if ('' !== $this->name) {
            $data['name'] = $this->name;
        }

        return $data;
    }
}
