<?php

namespace App\Application\Clothes\Provider\CategoryProvider;

use App\Entity\Category\Category;
use App\Repository\Category\CategoryRepository;

final readonly class CategoryProvider
{
    public function __construct(
        private CategoryRepository $repository,
    ) {
    }

    /** @return list<array{0: Category, collectionsCount: int|string}> */
    public function search(string $search, string $sort, string $direction): array
    {
        return $this->repository->searchWithCollectionsCount(
            $search,
            $sort,
            $direction,
        );
    }

    public function slugExists(string $slug, ?Category $current = null): bool
    {
        return $this->repository->slugExists($slug, $current);
    }
}
