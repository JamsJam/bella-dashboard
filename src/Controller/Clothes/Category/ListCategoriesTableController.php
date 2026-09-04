<?php

namespace App\Controller\Clothes\Category;

use App\UI\Clothes\Category\CategoryViewFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ListCategoriesTableController extends AbstractController
{
    #[Route('/clothes/categories/table', name: 'app_clothes_categories_table', methods: ['GET'])]
    public function list(Request $request, CategoryViewFactory $views): JsonResponse
    {
        $table = $views->table(
            (string) $request->query->get('search', ''),
            (string) $request->query->get('sort', 'name'),
            (string) $request->query->get('direction', 'asc'),
        );

        return $this->json([
            'html' => $this->renderView('ui/components/data-table/_rows.html.twig', [
                'columns' => $table['columns'],
                'rows' => $table['rows'],
            ]),
        ]);
    }
}
