<?php

namespace App\Controller\Clothes\Collection;

use App\Service\BreadscrumbsService;
use App\UI\Clothes\Collection\CollectionViewFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ListCollectionsController extends AbstractController
{
    #[Route('/collections', name: 'app_clothe_collection', methods: ['GET'])]
    public function list(
        Request $request,
        CollectionViewFactory $viewFactory,
        BreadscrumbsService $breadscrumbs,
    ): Response {
        return $this->render('clothes/collections/index.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve(
                (string) $request->attributes->get('_route'),
                currentLabel: 'Collections',
            ),
            'tabs' => [
                ['id' => 'add', 'label' => 'Ajouter une collection', 'href' => $this->generateUrl('app_clothe_collection_add'), 'isActive' => false],
                ['id' => 'back', 'label' => 'Retour', 'href' => $this->generateUrl('app_clothes'), 'isActive' => false],
            ],
            'table' => $viewFactory->table(
                (string) $request->query->get('search', ''),
                (string) $request->query->get('sort', 'name'),
                (string) $request->query->get('direction', 'asc'),
            ),
        ]);
    }
}
