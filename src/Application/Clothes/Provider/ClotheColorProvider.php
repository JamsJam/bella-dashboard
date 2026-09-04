<?php

namespace App\Application\Clothes\Provider;

use App\Entity\Clothes\Clothescolor;
use App\Repository\Clothes\ClothescolorRepository;

final readonly class ClotheColorProvider
{
    public function __construct(
        private ClothescolorRepository $repository,
    ) {
    }

    /** @return list<Clothescolor> */
    public function findAllByName(): array
    {
        return $this->repository->findBy([], ['name' => 'ASC']);
    }

    public function findOneByName(string $name): ?Clothescolor
    {
        return $this->repository->findOneBy(['name' => $name]);
    }
}
