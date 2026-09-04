<?php

namespace App\Controller\Clothes\Clothe\Catalog;

use App\Application\Clothes\Mapper\ClotheProductGridItemMapper;
use App\Application\Clothes\Services\Clothe\ClotheService;
use App\Entity\Clothes\ClothesVariant;
use App\Enum\ClotheStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class SearchClothesController extends AbstractController
{
    #[Route('/clothes/search', name: 'app_search_clothes', methods: ['GET'])]
    public function search(
        #[MapQueryParameter] ?string $search,
        #[MapQueryParameter] ?int $category,
        #[MapQueryParameter] ?int $collection,
        #[MapQueryParameter] ?bool $bestseller,
        #[MapQueryParameter] ?string $status,
        ClotheService $clotheService,
        ClotheProductGridItemMapper $productGridItemMapper,
    ): JsonResponse {
        $variantGroups = $clotheService->getDistinctClotheByName(
            sortBy: 'name',
            direction: 'asc',
            query: $search ?? '',
            category: $category,
            collection: $collection,
            bestsellerOnly: $bestseller ?? false,
            status: ClotheStatus::tryFrom((string) $status),
        );

        return $this->json([
            'items' => array_map(
                static fn (ClothesVariant $variant): array => $productGridItemMapper
                    ->mapVariantGroup($variant)
                    ->toArray(),
                $variantGroups,
            ),
        ]);
    }
}
