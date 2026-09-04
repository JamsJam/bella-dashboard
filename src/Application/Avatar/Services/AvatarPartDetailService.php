<?php

namespace App\Application\Avatar\Services;

use App\Application\Avatar\Dto\AvatarPartDetailDto;
use App\Application\Avatar\Exception\AvatarPartNotFoundException;
use App\Application\Avatar\Mapper\AvatarPartViewMapper;
use App\Application\Avatar\Provider\AvatarPartDetailProvider;
use App\Entity\Avatar\Faces\Faces;

final readonly class AvatarPartDetailService
{
    public function __construct(
        private AvatarResolverService $resolver,
        private AvatarPartDetailProvider $provider,
        private AvatarPartViewMapper $mapper,
    ) {
    }

    public function getDetail(string $part, int $id): AvatarPartDetailDto
    {
        $entityClass = $this->resolver->resolveEntity($part);
        $avatarPart = $this->provider->find($entityClass, $id);

        if (null === $avatarPart) {
            throw new AvatarPartNotFoundException($part, $id);
        }

        return new AvatarPartDetailDto(
            part: $part,
            avatar: $this->mapper->map($avatarPart),
            similarAvatars: array_map(
                $this->mapper->map(...),
                $this->provider->findSimilar($entityClass, $avatarPart),
            ),
            accessoryFaces: array_map(
                $this->mapper->map(...),
                $this->provider->findAccessoryFaces($avatarPart),
            ),
            showAccessoryFacesSection: $avatarPart instanceof Faces && null === $avatarPart->getAccessory(),
        );
    }
}
