<?php

namespace App\ApiResource\Search;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use App\State\Search\VariantSearchProvider;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/search/{category}',
            requirements: ['category' => '[a-zA-Z0-9_-]+'],
            parameters: [
                'color' => new QueryParameter(
                    description: 'Couleurs recherchées. Exemple : color[]=Rouge&color[]=Bleu.',
                    schema: ['type' => 'array', 'items' => ['type' => 'string']],
                    castToArray: true,
                ),
                'size' => new QueryParameter(
                    description: 'Tailles recherchées. Exemple : size[]=S&size[]=M.',
                    schema: ['type' => 'array', 'items' => ['type' => 'string']],
                    castToArray: true,
                ),
                'price' => new QueryParameter(
                    description: 'Prix minimum puis maximum, en centimes. Exemple : price[]=2000&price[]=8000.',
                    schema: [
                        'type' => 'array',
                        'items' => ['type' => 'integer', 'minimum' => 0],
                        'minItems' => 1,
                        'maxItems' => 2,
                    ],
                    castToArray: true,
                ),
            ],
            paginationEnabled: false,
            provider: VariantSearchProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final readonly class VariantCardDTO
{
    /**
     * @param list<string> $images
     * @param list<string> $colors
     * @param list<string> $sizes
     */
    public function __construct(
        public string $name,
        public string $slug,
        public int $price,
        public ?string $image = null,
        public array $images = [],
        public array $colors = [],
        public array $sizes = [],
    ) {
    }
}
