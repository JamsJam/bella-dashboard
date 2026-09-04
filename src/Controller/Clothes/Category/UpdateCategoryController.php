<?php

namespace App\Controller\Clothes\Category;

use App\Application\Clothes\Services\Category\CategoryManagementService;
use App\Entity\Category\Category;
use App\Notifier\Services\FlashService;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UpdateCategoryController extends AbstractController
{
    #[Route(
        '/clothes/categories/{id}',
        name: 'app_clothe_category_update',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    public function update(
        Category $category,
        Request $request,
        CategoryManagementService $service,
        FlashService $flash,
        LoggerService $logger,
    ): Response {
        $id = (int) $category->getId();
        if (!$this->isCsrfTokenValid('category_edit_' . $id, (string) $request->request->get('_csrf_token', ''))) {
            $flash->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for category update.', ['category_id' => $id]);

            return $this->redirectToRoute('app_clothe_category_show', ['id' => $id]);
        } try {
            $service->update($category, $request);
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            $flash->error($e->getMessage());
            $logger->exception($e, 'Category update rejected.', ['category_id' => $id]);

            return $this->redirectToRoute('app_clothe_category_show', ['id' => $id]);
        }$flash->success('Categorie modifiee.');
        $logger->info('Category updated.', ['category_id' => $id]);

        return $this->redirectToRoute('app_clothe_category_show', ['id' => $id]);
    }
}
