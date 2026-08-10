<?php

namespace App\Application\Avatar\Mapper;

use App\Application\Avatar\Dto\AvatarTempViewDto;
use App\Entity\AvatarTemp;

final readonly class AvatarTempViewMapper
{
    public function map(AvatarTemp $avatarTemp): AvatarTempViewDto
    {
        $id = $avatarTemp->getId();
        if (null === $id) {
            throw new \LogicException('An avatar temporary view requires a persisted entity.');
        }

        return new AvatarTempViewDto(
            id: $id,
            originalName: $avatarTemp->getOriginalName(),
            storedName: $avatarTemp->getStoredName(),
            status: $avatarTemp->getStatus(),
            finalName: $avatarTemp->getFinalName(),
        );
    }
}
