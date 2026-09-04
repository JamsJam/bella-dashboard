<?php

namespace App\Application\Avatar\Factory;

use App\Application\Avatar\Services\AvatarResolverService;

final readonly class AvatarPartFactory
{
    public function __construct(
        private AvatarResolverService $avatarResolverService,
    ) {
    }

    public function createFromCategory(string $category): object
    {
        $entityClass = $this->resolveEntityClass($category);

        return new $entityClass();
    }

    public function resolveEntityClass(string $category): string
    {
        $entityClass = $this->avatarResolverService->resolveEntity($category);

        if (null === $entityClass || !class_exists($entityClass)) {
            throw new \InvalidArgumentException(sprintf('Unknown avatar part category "%s".', $category));
        }

        return $entityClass;
    }
}
