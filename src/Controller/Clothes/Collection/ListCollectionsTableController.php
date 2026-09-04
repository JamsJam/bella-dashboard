<?php

namespace App\Controller\Clothes\Collection;

use App\UI\Clothes\Collection\CollectionViewFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ListCollectionsTableController extends AbstractController
{
    #[Route('/collections/table', name: 'app_clothe_collection_table', methods: ['GET'])]
    public function list(Request $request, CollectionViewFactory $viewFactory): JsonResponse
    {
        $table = $viewFactory->table(
            (string) $request->query->get('search', ''),
            (string) $request->query->get('sort', 'name'),
            (string) $request->query->get('direction', 'asc'),
        );

        return $this->json(['html' => $this->renderView('ui/components/data-table/_rows.html.twig', [
            'columns' => $table['columns'],
            'rows' => $table['rows'],
        ])]);
    }
}
