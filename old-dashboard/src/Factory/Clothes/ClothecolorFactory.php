<?php

namespace App\Factory\Clothes;

use App\Entity\Clothes\Clothescolor;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\Clothes\ClothescolorRepository;


final class ClothecolorFactory
{
    private array $cache = [];

    public function __construct(
        private readonly ClothescolorRepository $clothescolorRepository,
        private readonly EntityManagerInterface $entityManager,
    ){}

    public function createOrGet(string $colorName):Clothescolor
    {
        $normalizedName =  $this->normalizeColorName($colorName);

        $color =  $this->fetchColorByNameFromCache($normalizedName) ?? $this->fetchColorByNameFromEntity($normalizedName)  ?? $this->createColor($normalizedName);

        return $color;
        
    }

    private function fetchColorByNameFromCache(string $name): ?Clothescolor
    {
        if (empty($this->cache)) {
            return null;
        }
        
        return array_find($this->cache, function(?Clothescolor $cacheItem) use ($name)
        {
            return $cacheItem->getName() == $name;
        });
    }

    private function fetchColorByNameFromEntity(string $name): ?Clothescolor
    {
        $color = $this->clothescolorRepository->findOneBy(['name' => $name]) ;
        if($color){
            $this->cache[] = $color;
        }
        return $color;
    }

    private function createColor(string $name): Clothescolor
    {
        $today = new \DateTimeImmutable();
        $color = new Clothescolor();
        $color->setName($name);
        $color->setCreatedAt($today);
        $color->setEditedAt($today);
        // $this->saveColor($color);
        $this->cache[] = $color;
        return $color;
    }

    private function normalizeColorName(string $name): string
    {
        $name = trim($name);                 // Supprime les espaces autour
        $name = mb_strtolower($name);        // Tout en minuscules
        $name = preg_replace('/\s+/', ' ', $name); // Supprime les espaces multiples
        return $name;
    }


}
