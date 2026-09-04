<?php

namespace App\Application\Clothes\Provider\ClotheProvider;

use App\Entity\Category\Category;
use App\Entity\Clothes\Clothes;
use App\Entity\Collections\Collections;
use App\Enum\ClotheStatus;
use App\Repository\Category\CategoryRepository;
use App\Repository\Clothes\ClothesRepository;
use App\Repository\Clothes\ClothesVariantRepository;
use App\Repository\Collections\CollectionsRepository;

final class ClotheProvider
{
    public function __construct(
        private ClothesRepository $clothesRepository,
        private ClothesVariantRepository $clothesVariantRepository,
        private CategoryRepository $categoryRepository,
        private CollectionsRepository $collectionsRepository,
    ) {
    }

    public function searchDistinctClothes(
        ?string $orderBy = 'id',
        ?string $direction = null,
        ?string $query = null,
        ?int $limit = 10,
        ?int $offset = 0,
    ): array {
        $allowedOrder = match ($orderBy) {
            'collection' => 'col.name',
            'category' => 'cat.name',
            default => 'c.name',
        };
        $allowedDirecrtions = match (strtolower($direction)) {
            'desc' => 'desc',
            default => 'asc',
        };

        $clothes = $this->clothesRepository->findDistinctBySlug($allowedOrder, $allowedDirecrtions, $query, $limit, $offset) ?? [];

        return $clothes;
    }

    public function searchDistinctClothesByName(
        ?string $orderBy = 'name',
        ?string $direction = 'asc',
        ?string $query = null,
        ?int $category = null,
        ?int $collection = null,
        bool $bestsellerOnly = false,
        ?ClotheStatus $status = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        $allowedOrder = match ($orderBy) {
            'collection' => 'collection.name',
            'category' => 'category.name',
            'color' => 'color.name',
            default => 'c.name',
        };
        $allowedDirections = match (strtolower((string) $direction)) {
            'desc' => 'desc',
            default => 'asc',
        };

        return $this->clothesVariantRepository->findGroupsBySlug(
            $allowedOrder,
            $allowedDirections,
            $query,
            $category,
            $collection,
            $bestsellerOnly,
            $status,
            $limit,
            $offset,
        );
    }

    public function getBestSellers(?int $limit): array
    {
        $clothes = $this->clothesRepository->findBestSellersDistinctBySlug($limit) ?? [];

        return $clothes;
    }

    /**
     * @return Clothes[]
     */
    public function getBestSellerEntities(?int $limit = null): array
    {
        return $this->clothesRepository->findDistinctBestsellerEntities($limit) ?? [];
    }

    public function getClotheInCarousel(?int $limit): array
    {
        $clothes = $this->clothesRepository->findInCarouselDistinctBySlug($limit) ?? [];

        return $clothes;
    }

    public function getClotheVariantsBySlug(string $slug): array
    {
        return $this->clothesRepository->findVariantsBySlug($slug);
    }

    /**
     * @return list<Category>
     */
    public function findCategoriesByName(): array
    {
        return $this->categoryRepository->findBy([], ['name' => 'ASC']);
    }

    /**
     * @return list<Collections>
     */
    public function findCollectionsByName(): array
    {
        return $this->collectionsRepository->findBy([], ['name' => 'ASC']);
    }
}
