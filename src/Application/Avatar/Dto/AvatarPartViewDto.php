<?php

namespace App\Application\Avatar\Dto;

final readonly class AvatarPartViewDto
{
    /**
     * @param list<string>                $imageUrls
     * @param array<string, string>       $imageSides
     * @param array<string, string|array> $attributes
     */
    public function __construct(
        public ?int $id,
        public string $name,
        public string $imageUrl,
        public array $imageUrls,
        public array $imageSides,
        public array $attributes,
    ) {
    }
}
