<?php

namespace App\ApiResource\Avatar;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Avatar\AvatarColorProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/avatar/mouth-colors',
            provider: AvatarColorProvider::class,
            extraProperties: ['colorType' => 'mouth'],
        ),
        new Get(
            uriTemplate: '/avatar/hair-colors',
            provider: AvatarColorProvider::class,
            extraProperties: ['colorType' => 'hair'],
        ),
        new Get(
            uriTemplate: '/avatar/eyes-colors',
            provider: AvatarColorProvider::class,
            extraProperties: ['colorType' => 'eyes'],
        ),
        new Get(
            uriTemplate: '/avatar/eyebrow-colors',
            provider: AvatarColorProvider::class,
            extraProperties: ['colorType' => 'eyebrow'],
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final readonly class AvatarColorList
{
    /** @param list<AvatarColor> $colors */
    public function __construct(
        public string $type,
        public array $colors,
    ) {
    }
}
