<?php

namespace App\Application\Avatar\Services;

use App\Application\Avatar\Dto\AvatarColorModalDto;
use App\Application\Avatar\Dto\AvatarColorTabDto;
use App\Application\Avatar\Exception\AvatarColorNotFoundException;
use App\Application\Avatar\Mapper\AvatarColorMapper;
use App\Application\Avatar\Provider\AvatarColorProvider;
use App\Application\Avatar\Resolver\AvatarColorTypeResolver;

final readonly class AvatarColorService
{
    public function __construct(
        private AvatarColorTypeResolver $resolver,
        private AvatarColorProvider $provider,
        private AvatarColorMapper $mapper,
    ) {
    }

    public function getModal(string $activeType): AvatarColorModalDto
    {
        $definition = $this->resolver->resolve($activeType);
        $colors = [];

        foreach ($this->provider->findAll($definition->entityClass) as $color) {
            $colors[] = $this->mapper->map(
                $color,
                $activeType,
                count($this->provider->associatedElements($color, $definition->associationMethods)),
            );
        }

        return new AvatarColorModalDto(
            activeType: $activeType,
            activeLabel: $definition->label,
            colors: $colors,
            tabs: array_map(
                static fn ($type): AvatarColorTabDto => new AvatarColorTabDto(
                    type: $type->type,
                    label: $type->label,
                    active: $type->type === $activeType,
                ),
                $this->resolver->all(),
            ),
        );
    }

    public function delete(string $type, int $id): int
    {
        $definition = $this->resolver->resolve($type);
        $color = $this->provider->find($definition->entityClass, $id);

        if (null === $color) {
            throw new AvatarColorNotFoundException($type, $id);
        }

        $associatedElements = $this->provider->associatedElements($color, $definition->associationMethods);
        $this->provider->remove($color, $associatedElements);

        return count($associatedElements);
    }
}
