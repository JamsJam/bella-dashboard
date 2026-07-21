<?php

namespace App\ApiResource\Avatar;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Avatar\EyebrowsByColorProvider;
use App\State\Avatar\EyesByColorProvider;
use App\State\Avatar\MouthsByColorProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/avatar/eyes-colors/{id}/eyes',
            requirements: ['id' => '\\d+'],
            provider: EyesByColorProvider::class,
        ),
        new Get(
            uriTemplate: '/avatar/eyebrow-colors/{id}/eyebrows',
            requirements: ['id' => '\\d+'],
            provider: EyebrowsByColorProvider::class,
        ),
        new Get(
            uriTemplate: '/avatar/mouth-colors/{id}/mouths',
            requirements: ['id' => '\\d+'],
            provider: MouthsByColorProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final readonly class AvatarPartByColorList
{
    /** @param list<AvatarPartByColor> $items */
    public function __construct(
        public int $colorId,
        public string $type,
        public array $items,
    ) {
    }
}
