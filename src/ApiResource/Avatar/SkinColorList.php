<?php

namespace App\ApiResource\Avatar;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Avatar\SkinColorProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/avatar/skin-colors',
            provider: SkinColorProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final readonly class SkinColorList
{
    /** @param list<SkinColor> $skinColors */
    public function __construct(
        public array $skinColors,
    ) {
    }
}
