<?php

namespace App\Application\Config\Dto\Page\Homepage\Section;

use App\Application\Config\Dto\Page\Homepage\Item\ManualItemDto;

final class ManualDto
{
    public function __construct(
        public ?string $title = null,
        public array $list = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['title']) ? (string) $data['title'] : null,
            array_values(array_map(
                static fn (array $item): ManualItemDto => ManualItemDto::fromArray($item),
                array_filter($data['list'] ?? [], 'is_array'),
            )),
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'list' => array_map(static fn (ManualItemDto $item): array => $item->toArray(), $this->list),
        ];
    }
}
