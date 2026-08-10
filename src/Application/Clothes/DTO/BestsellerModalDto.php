<?php

namespace App\Application\Clothes\DTO;

use App\UI\ProductGrid\ProductGridItemModel;

final readonly class BestsellerModalDto
{
    /** @param list<ProductGridItemModel> $bestsellers */
    public function __construct(
        public array $bestsellers,
        public int $maxItems,
        public ?string $errorMessage = null,
    ) {
    }
}
