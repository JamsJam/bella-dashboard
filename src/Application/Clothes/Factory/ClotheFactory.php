<?php

namespace App\Application\Clothes\Factory;

use App\Entity\Clothes\Clothes;
use App\Entity\Collections\Collections;

final readonly class ClotheFactory
{
    public function create(
        string $name,
        ?int $price,
        ?Collections $collection,
    ): Clothes {
        $now = new \DateTimeImmutable();

        return (new Clothes())
            ->setName($name)
            ->setPrice($price)
            ->setCollection($collection)
            ->setCreatedAt($now)
            ->setEditedAt($now);
    }
}
