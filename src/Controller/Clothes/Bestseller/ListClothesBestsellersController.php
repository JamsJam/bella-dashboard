<?php

namespace App\Controller\Clothes\Bestseller;

use App\Application\Clothes\Services\Bestseller\BestsellerViewService;
use App\Application\Clothes\Services\Bestseller\ClotheBestsellerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ListClothesBestsellersController extends AbstractController
{
    #[Route('/clothes/bestsellers/list', name: 'app_clothes_bestsellers', methods: ['GET'])]
    public function list(BestsellerViewService $viewService): JsonResponse
    {
        return $this->json([
            'success' => true,
            'cacheKey' => ClotheBestsellerService::CACHE_KEY,
            'maxItems' => $viewService->maxItems(),
            'items' => array_map(static fn ($item): array => $item->toArray(), $viewService->items()),
        ]);
    }
}
