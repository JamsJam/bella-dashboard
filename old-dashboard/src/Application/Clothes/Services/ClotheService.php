<?php

namespace App\Application\Clothes\Services;

use App\Application\Clothes\Provider\ClotheProvider\ClotheProvider;

final class ClotheService 
{
    public function __construct(
        private ClotheProvider $clotheProvider,
    ){}

    public function getDistinctClothe(?string $sortBy = 'id', ?string $direction = 'asc', ?string $query = '',?int $limit = null, ?int $offset = null) : array
    {

        $clothes = $this->clotheProvider->searchDistinctClothes($sortBy, $direction, $query, $limit, $offset) ?? [];
        // $clothes = [];
        
        return $clothes;
    }

    public function getBestselledClothe(?int $limit = null) : array
    {

        $clothes = $this->clotheProvider->getBestSellers($limit) ?? [];
        // $clothes = [];
        return $clothes;
    }

    public function getClotheInCarousel(?int $limit = null) : array
    {

        $clothes = $this->clotheProvider->getClotheInCarousel($limit) ?? [];
        // $clothes = [];
        return $clothes;
    }

    public function getTotalItems() : array
    {

        // $totalItems = $this->clotheProvider->gettotalItems() ?? [];
        $clothes = [];
        // return $totalItems;
        return $clothes;
    }
}
