<?php

namespace App\Application\Config\Dto\Page\Homepage\Item;

final class ReturnStepDto
{
    public function __construct(public string $title = '', public string $icon = '')
    {
    }

    public static function fromArray(array $data): self
    {
        return new self((string) ($data['title'] ?? ''), (string) ($data['icon'] ?? ''));
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
