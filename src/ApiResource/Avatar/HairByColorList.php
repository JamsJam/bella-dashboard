<?php

namespace App\ApiResource\Avatar;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Avatar\HairByColorProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/avatar/hair-colors/{id}/hairs',
            requirements: ['id' => '\\d+'],
            provider: HairByColorProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final readonly class HairByColorList
{
    /** @param list<HairByColor> $hairs */
    public function __construct(
        public int $hairColorId,
        public array $hairs,
    ) {
    }
}
