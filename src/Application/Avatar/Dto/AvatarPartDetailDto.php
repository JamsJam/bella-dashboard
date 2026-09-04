<?php

namespace App\Application\Avatar\Dto;

final readonly class AvatarPartDetailDto
{
    /**
     * @param list<AvatarPartViewDto> $similarAvatars
     * @param list<AvatarPartViewDto> $accessoryFaces
     */
    public function __construct(
        public string $part,
        public AvatarPartViewDto $avatar,
        public array $similarAvatars,
        public array $accessoryFaces,
        public bool $showAccessoryFacesSection,
    ) {
    }
}
