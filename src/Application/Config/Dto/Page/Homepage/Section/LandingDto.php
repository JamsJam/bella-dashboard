<?php

namespace App\Application\Config\Dto\Page\Homepage\Section;

final class LandingDto
{
    public function __construct(
        public bool $isFullscreen = false,
        public string $title = '',
        public string $subtitle = '',
        public string $text = '',
        public string $image = '',
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (bool) ($data['isFullscreen'] ?? false),
            (string) ($data['title'] ?? ''),
            (string) ($data['subtitle'] ?? ''),
            (string) ($data['text'] ?? ''),
            (string) ($data['image'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
