<?php

namespace App\Controller\Clothes\Category;

use App\Application\Clothes\Persister\CategoryPersister;
use App\Entity\Category\Category;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteCategoryController extends AbstractController
{
    #[Route(
        '/clothes/categories/{id}/delete',
        name: 'app_clothe_category_delete',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    public function delete(
        Category $category,
        Request $request,
        CategoryPersister $persister,
        LoggerService $logger,
    ): Response {
        $id = (int) $category->getId();
        if (!$this->isCsrfTokenValid('category_delete_' . $id, (string) $request->request->get('_csrf_token', ''))) {
            $logger->warning('Invalid CSRF token for category deletion.', ['category_id' => $id]);

            return new Response('Invalid CSRF token.', 403);
        } $rowId = 'category-row-' . $id;
        $persister->delete($category);
        $logger->info('Category deleted.', ['category_id' => $id, 'row_id' => $rowId]);

        return $this->render(
            'clothes/categories/turbo/delete.stream.html.twig',
            ['rowId' => $rowId],
            new Response(headers: ['Content-Type' => 'text/vnd.turbo-stream.html']),
        );
    }
}
