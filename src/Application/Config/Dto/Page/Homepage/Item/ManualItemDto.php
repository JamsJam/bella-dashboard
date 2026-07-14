<?php

namespace App\Application\Config\Dto\Page\Homepage\Item;

final class ManualItemDto
{
    public function __construct(public string $text = '', public string $image = '')
    {
    }

    public static function fromArray(array $data): self
    {
        return new self((string) ($data['text'] ?? ''), (string) ($data['image'] ?? ''));
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
