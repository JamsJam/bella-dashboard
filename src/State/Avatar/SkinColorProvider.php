<?php

namespace App\State\Avatar;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Avatar\SkinColor;
use App\ApiResource\Avatar\SkinColorList;
use App\Repository\Avatar\SkincolorRepository;

/** @implements ProviderInterface<SkinColorList> */
final readonly class SkinColorProvider implements ProviderInterface
{
    public function __construct(
        private SkincolorRepository $skinColorRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SkinColorList
    {
        $skinColors = [];

        foreach ($this->skinColorRepository->findBy([], ['name' => 'ASC']) as $skinColor) {
            $id = $skinColor->getId();
            if (null === $id) {
                continue;
            }

            $skinColors[] = new SkinColor(
                id: $id,
                name: (string) $skinColor->getName(),
                hexa: $skinColor->getHexa(),
            );
        }

        return new SkinColorList($skinColors);
    }
}
