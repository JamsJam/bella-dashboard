<?php

namespace App\ApiResource\Avatar;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Avatar\MorphologiesBySkinColorProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/avatar/skin-colors/{id}/morphologies',
            requirements: ['id' => '\\d+'],
            provider: MorphologiesBySkinColorProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final readonly class MorphologyBySkinColorList
{
    /** @param list<MorphologyBySkinColor> $morphologies */
    public function __construct(
        public int $skinColorId,
        public array $morphologies,
    ) {
    }
}
