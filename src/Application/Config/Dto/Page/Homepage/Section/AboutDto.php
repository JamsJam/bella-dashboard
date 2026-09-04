<?php

namespace App\Application\Config\Dto\Page\Homepage\Section;

final class AboutDto
{
    public function __construct(public string $title = '', public string $text = '')
    {
    }

    public static function fromArray(array $data): self
    {
        return new self((string) ($data['title'] ?? ''), (string) ($data['text'] ?? ''));
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
