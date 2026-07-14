<?php

namespace App\Application\Config\Dto\Page\Categories\Section;

final class BandeauDto
{
    public function __construct(
        public string $title = '',
        public string $cta = '',
        public string $background = '',
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['title'] ?? ''),
            (string) ($data['cta'] ?? ''),
            (string) ($data['background'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
