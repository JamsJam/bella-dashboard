<?php

namespace App\ApiResource\Variant;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Variant\VariantDetailProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/variant/{slug}',
            requirements: ['slug' => '[a-zA-Z0-9_-]+'],
            provider: VariantDetailProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final readonly class VariantDetailDTO
{
    /**
     * @param list<string> $images
     * @param list<string> $sizes
     * @param list<VariantColorDTO> $colors
     * @param list<RelatedVariantDTO> $relatedProducts
     */
    public function __construct(
        public string $name,
        public string $slug,
        public int $price,
        public VariantCategoryDTO $category,
        public ?string $description = null,
        public ?string $metadescription = null,
        public ?string $image = null,
        public array $images = [],
        public array $sizes = [],
        public ?SizeGuideDTO $sizeGuide = null,
        public array $colors = [],
        public array $relatedProducts = [],
    ) {
    }
}
