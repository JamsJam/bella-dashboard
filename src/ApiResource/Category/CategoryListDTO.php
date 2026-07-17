<?php

namespace App\ApiResource\Category;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\State\Category\CategoryListProvider;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/category/{category}',
            requirements: ['category' => '[a-zA-Z0-9_-]+'],
            paginationEnabled: false,
            provider: CategoryListProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final readonly class CategoryListDTO
{
    /**
     * @param list<string> $images
     */
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $image = null,
        public array $images = [],
    ) {
    }
}
