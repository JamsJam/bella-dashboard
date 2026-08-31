<?php

namespace App\ApiResource\Avatar;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Avatar\AccessorizedFaceProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/avatar/faces/{id}/accessories',
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
        public int $faceId,
        public int $skinColorId,
        public int $faceShapeId,
        public array $items,
    ) {
    }
}
