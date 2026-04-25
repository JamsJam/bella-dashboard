<?php 

namespace App\Application\Clothes\Provider\ClotheProvider;

use App\Entity\Clothes\Clothes;
use App\Repository\Clothes\ClothesRepository;



final class ClotheProvider 
{
    public function __construct(
        private ClothesRepository $clothesRepository
    ){}

    public function searchDistinctClothes(
        ?string $orderBy="id", 
        ?string $direction=null,
        ?string $query=null,
        ?int $limit=10,
        ?int $offset=0
        ) :array
    {
        $allowedOrder = match ($orderBy) {
            "collection" => 'col.name',
            "category" => 'cat.name',
            default => 'c.name'
        };
        $allowedDirecrtions = match (strtolower($direction)) {
            "desc" => 'desc',
            default => 'asc'
        };

        $clothes = $this->clothesRepository->findDistinctBySlug($allowedOrder, $allowedDirecrtions ,$query, $limit, $offset) ?? [] ;

        return $clothes;
    }

    public function getBestSellers(?int $limit) :array
    {
        $clothes = $this->clothesRepository->findBestSellersDistinctBySlug($limit) ?? [] ;

        return $clothes;
    }

    public function getClotheInCarousel(?int $limit) :array
    {
        $clothes = $this->clothesRepository->findBestSellersDistinctBySlug($limit) ?? [] ;

        return $clothes;
    }
}
