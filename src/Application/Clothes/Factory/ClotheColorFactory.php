<?php

namespace App\Application\Clothes\Factory;

use App\Entity\Clothes\Clothescolor;

final readonly class ClotheColorFactory
{
    public function create(string $name, string $hexadecimal): Clothescolor
    {
        $now = new \DateTimeImmutable();

        return (new Clothescolor())
            ->setName(trim($name))
            ->setHexa(strtolower(ltrim($hexadecimal, '#')))
            ->setCreatedAt($now)
            ->setEditedAt($now);
    }
}
