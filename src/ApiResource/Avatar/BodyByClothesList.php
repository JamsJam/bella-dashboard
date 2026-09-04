<?php

namespace App\ApiResource\Avatar;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use App\State\Avatar\BodiesByClothesProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/avatar/bodies',
            parameters: [
                'clothes' => new QueryParameter(
                    schema: ['type' => 'string'],
                    description: 'Slug du groupe de variantes, ou "none" pour les corps sans vêtement.',
                    required: true,
                ),
            ],
            provider: BodiesByClothesProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final readonly class BodyByClothesList
{
    /** @param list<BodyByClothes> $bodies */
    public function __construct(
        public string $clothes,
        public array $bodies,
    ) {
    }
}
