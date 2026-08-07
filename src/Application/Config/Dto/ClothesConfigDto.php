<?php

namespace App\Application\Config\Dto;

final class ClothesConfigDto
{
    /** @param list<SizeGuideItemDto> $sizeGuideItems */
    public function __construct(
        public int $bestsellerCount = 4,
        public int $featuredCount = 4,
        public array $sizeGuideItems = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            bestsellerCount: max(0, (int) ($data['bestseller_count'] ?? 4)),
            featuredCount: max(0, (int) ($data['featured_count'] ?? 4)),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'bestseller_count' => $this->bestsellerCount,
            'featured_count' => $this->featuredCount,
        ];
    }
}
