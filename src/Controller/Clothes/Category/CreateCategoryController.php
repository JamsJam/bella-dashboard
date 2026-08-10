<?php

namespace App\Controller\Clothes\Category;

use App\Application\Clothes\Services\Category\CategoryManagementService;
use App\Notifier\Services\FlashService;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CreateCategoryController extends AbstractController
{
    #[Route('/clothes/categories', name: 'app_clothes_categories_create', methods: ['POST'])]
    public function create(
        Request $request,
        CategoryManagementService $service,
        FlashService $flash,
        LoggerService $logger,
    ): Response {
        if (!$this->isCsrfTokenValid('category_create', (string) $request->request->get('_csrf_token', ''))) {
            $flash->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for category creation.');

            return $this->redirectToRoute('app_clothes_categories');
        }

        try {
            $category = $service->create($request);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            $flash->error($e->getMessage());
            $logger->exception($e, 'Category creation rejected.');

            return $this->redirectToRoute('app_clothes_categories');
        }

        $flash->success('Categorie creee hors-ligne.');
        $logger->info('Category created.', [
            'category_id' => $category->getId(),
            'category_name' => $category->getName(),
        ]);

        return $this->redirectToRoute('app_clothe_category_show', ['id' => $category->getId()]);
    }
}
