<?php

namespace App\Factory\Clothes;

use App\Entity\Clothes\Clothessize;
use App\Repository\Clothes\ClothessizeRepository;


final class ClothesizeFactory
{
    public function __construct(
        private readonly ClothessizeRepository $clothessizeRepository
    ){}

    public function createOrGet(string $colorName):Clothessize
    {
        $normalizedName =  $this->normalizeColorName($colorName);

        $size = $this->fetchColorByName($normalizedName) ?? $this->createColor($normalizedName);

        return $size;
        
    }

    private function fetchColorByName(string $name): ?Clothessize
    {
        return $this->clothessizeRepository->findOneBy(['name' => $name]);
    }

    private function createColor(string $name): Clothessize
    {
        $today = new \DateTimeImmutable();
        $size = new Clothessize();
        $size->setName($name);
        $size->setCreatedAt($today);
        $size->setEditedAt($today);
        return $size;
    }

    private function normalizeColorName(string $name): string
    {
        $name = trim($name);                 // Supprime les espaces autour
        $name = mb_strtoupper($name);        // Tout en majuscules
        $name = preg_replace('/\s+/', ' ', $name); // Supprime les espaces multiples
        return $name;
    }
}
