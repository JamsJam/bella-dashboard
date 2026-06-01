<?php

namespace App\Application\Avatar\Services;

use App\Application\Avatar\Resolver\AvatarEntityResolver;
use App\Application\Avatar\Resolver\AvatarRepositoryResolver;

final class AvatarResolverService
{
    public function __construct(
        private readonly AvatarEntityResolver $entityResolver,
        private readonly AvatarRepositoryResolver $repositoryResolver,
    ) {
    }

    public function resolveEntity(string $part): ?string
    {
        return $this->entityResolver->resolve($part);
    }

    public function resolveRepository(string $part): ?string
    {
        return $this->repositoryResolver->resolve($part);
    }

    public function getAvailableParts(): array
    {
        return $this->entityResolver->getAvailableParts();
    }
}