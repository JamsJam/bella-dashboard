<?php

namespace App\ApiResource\Avatar;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Avatar\SkinColorAvatarPartProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/avatar/skin-colors/{id}/faces',
            requirements: ['id' => '\\d+'],
            provider: SkinColorAvatarPartProvider::class,
            extraProperties: ['avatarPart' => 'faces'],
        ),
        new Get(
            uriTemplate: '/avatar/skin-colors/{id}/noses',
            requirements: ['id' => '\\d+'],
            provider: SkinColorAvatarPartProvider::class,
            extraProperties: ['avatarPart' => 'noses'],
        ),
        new Get(
            uriTemplate: '/avatar/skin-colors/{id}/bodies',
            requirements: ['id' => '\\d+'],
            provider: SkinColorAvatarPartProvider::class,
            extraProperties: ['avatarPart' => 'bodies'],
        ),
        new Get(
            uriTemplate: '/avatar/skin-colors/{id}/morphotypes/{morphotypeId}/bodies',
            requirements: ['id' => '\\d+', 'morphotypeId' => '\\d+'],
            provider: SkinColorAvatarPartProvider::class,
            extraProperties: ['avatarPart' => 'bodies'],
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final readonly class SkinColorAvatarPartList
{
    /** @param list<SkinColorAvatarPart> $items */
    public function __construct(
        public int $skinColorId,
        public string $type,
        public array $items,
        public ?int $morphotypeId = null,
    ) {
    }
}
