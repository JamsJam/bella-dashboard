<?php

namespace App\Application\Config\Dto;

final class PageSectionContentItemDto
{
    public function __construct(
        public string $type = 'text',
        public string $value = '',
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: trim((string) ($data['type'] ?? 'text')),
            value: trim((string) ($data['value'] ?? '')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'value' => $this->value,
        ];
    }
}
