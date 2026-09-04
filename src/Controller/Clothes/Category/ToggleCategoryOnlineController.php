<?php

namespace App\Controller\Clothes\Category;

use App\Application\Clothes\Guard\Collection\CollectionOnlineGuard;
use App\Application\Clothes\Services\Category\CategoryPublicationService;
use App\Entity\Category\Category;
use App\Service\LoggerService;
use App\UI\Clothes\Category\CategoryViewFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ToggleCategoryOnlineController extends AbstractController
{
    #[Route(
        '/clothes/categories/{id}/online/{state}',
        name: 'app_clothes_categories_toggle_online',
        requirements: ['state' => 'on|off'],
        methods: ['POST'],
    )]
    public function toggle(
        Category $category,
        string $state,
        Request $request,
        CategoryPublicationService $publication,
        CollectionOnlineGuard $guard,
        CategoryViewFactory $views,
        LoggerService $logger,
    ): JsonResponse {
        $id = (int) $category->getId();
        if (!$this->isCsrfTokenValid('category_online_' . $id, (string) $request->headers->get('X-CSRF-TOKEN', ''))) {
            $logger->warning('Invalid CSRF token for category online toggle.', [
                'category_id' => $id,
                'state' => $state,
            ]);

            return $this->json(['success' => false, 'error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }if ('on' === $state && !$publication->publish($category)) {
            return $this->json(
                ['success' => false, 'error' => 'Category cannot be published.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }if ('off' === $state) {
            $publication->unpublish($category);
        }

        return $this->json([
            'success' => true,
            'isOnline' => $category->isOnline(),
            'collectionsHtml' => $this->renderView(
                'clothes/categories/_collections_list.html.twig',
                [
                    'category' => $category,
                    'collectionPublicationStates' => $views->collectionPublicationStates(
                        $category,
                        $guard,
                    ),
                ],
            ),
        ]);
    }
}
