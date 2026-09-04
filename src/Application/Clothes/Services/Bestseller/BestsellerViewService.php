<?php

namespace App\Application\Clothes\Services\Bestseller;

use App\Application\Clothes\DTO\BestsellerModalDto;
use App\Application\Clothes\Mapper\ClotheProductGridItemMapper;

final readonly class BestsellerViewService
{
    public function __construct(
        private ClotheBestsellerService $bestsellerService,
        private ClotheProductGridItemMapper $mapper,
    ) {
    }

    /** @return list<\App\UI\ProductGrid\ProductGridItemModel> */
    public function items(): array
    {
        return array_map($this->mapper->map(...), $this->bestsellerService->createCacheIfMissing());
    }

    public function modal(?array $clothes = null, ?string $errorMessage = null): BestsellerModalDto
    {
        $clothes ??= $this->bestsellerService->createCacheIfMissing();

        return new BestsellerModalDto(
            bestsellers: array_map($this->mapper->map(...), $clothes),
            maxItems: $this->bestsellerService->getMaxItems(),
            errorMessage: $errorMessage,
        );
    }

    public function maxItems(): int
    {
        return $this->bestsellerService->getMaxItems();
    }
}
