<?php

namespace App\ApiResource\Avatar;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Avatar\AccessorizedFaceProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/avatar/skin-colors/{id}/faces/accessories',
            requirements: ['id' => '\\d+'],
            provider: AccessorizedFaceProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final readonly class AccessorizedFaceList
{
    /** @param list<AccessorizedFace> $items */
    public function __construct(
        public int $skinColorId,
        public array $items,
    ) {
    }
}
