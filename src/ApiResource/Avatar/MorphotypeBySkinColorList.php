<?php

namespace App\ApiResource\Avatar;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Avatar\MorphotypesBySkinColorAndMorphologyProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/avatar/skin-colors/{id}/morphologies/{morphologyId}/morphotypes',
            requirements: ['id' => '\\d+', 'morphologyId' => '\\d+'],
            provider: MorphotypesBySkinColorAndMorphologyProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final readonly class MorphotypeBySkinColorList
{
    /** @param list<MorphotypeBySkinColor> $morphotypes */
    public function __construct(
        public int $skinColorId,
        public int $morphologyId,
        public array $morphotypes,
    ) {
    }
}
