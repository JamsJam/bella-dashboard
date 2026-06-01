<?php

namespace App\Controller\Avatar;


use App\Application\Avatar\Services\AvatarProductGridService;
use App\Application\Avatar\Services\SearchAvatarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;


final class SearchAvatarController extends AbstractController
{
    #[Route('/avatar/search',name: 'app_search_avatar',methods: ['GET'])]
    public function index(
        #[MapQueryParameter] ?string $partie,
        #[MapQueryParameter] ?string $search,
        #[MapQueryParameter] ?int $color,
        #[MapQueryParameter] ?int $shape,
        #[MapQueryParameter] ?int $skinColor,
        #[MapQueryParameter] ?int $morphologie,
        #[MapQueryParameter] ?int $morphotype,
        #[MapQueryParameter] ?int $clothes,
        #[MapQueryParameter] ?int $collection,
        Request $request, 
        SearchAvatarService $searchAvatarService
        ): JsonResponse
    {


        $items = $searchAvatarService->search(
            partie : $partie,
            filters : [
                'search' => $search,
                'color' => $color ?? 0,
                'shape' => $shape ?? 0,
                'skinColor' => $skinColor ?? 0,
                'morphologie' => $morphologie ?? 0,
                'morphotype' => $morphotype ?? 0,
                'clothes' => $clothes ?? 0,
                'collection' => $collection ?? 0,

            ]
            // $collection
        );

        return $this->json([
            'items' => $items,
            ]);
    }
    

    #[Route('/avatar/filters',name: 'app_search_avatar_filters',methods: ['GET'])]
    public function getFilters(
        #[MapQueryParameter] ?string $part,
        AvatarProductGridService $avatarProductGridService
    ): JsonResponse
    {
        $filters = $avatarProductGridService->getFiltersForPart($part);

        return new JsonResponse([
            'filters' => $filters,
            ],
            status: 200,
        );
    }
}
