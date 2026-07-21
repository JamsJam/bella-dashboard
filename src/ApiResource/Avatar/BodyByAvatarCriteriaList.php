<?php

namespace App\ApiResource\Avatar;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use App\State\Avatar\BodiesByAvatarCriteriaProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/avatar/skin-colors/{id}/morphologies/{morphologyId}/morphotypes/{morphotypeId}/bodies',
            requirements: [
                'id' => '\\d+',
                'morphologyId' => '\\d+',
                'morphotypeId' => '\\d+',
            ],
            parameters: [
                'clothes' => new QueryParameter(
                    schema: ['type' => 'string', 'nullable' => true],
                    description: 'Identifiant numérique ou slug du variant de vêtement. Peut être omis ou être null.',
                    required: false,
                ),
            ],
            provider: BodiesByAvatarCriteriaProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final readonly class BodyByAvatarCriteriaList
{
    /** @param list<BodyByAvatarCriteria> $bodies */
    public function __construct(
        public int $skinColorId,
        public int $morphologyId,
        public int $morphotypeId,
        public int|string|null $clothes,
        public array $bodies,
    ) {
    }
}
