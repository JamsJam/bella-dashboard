<?php

namespace App\Controller\Clothes\Category;

use App\Service\BreadscrumbsService;
use App\UI\Clothes\Category\CategoryViewFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ListCategoriesController extends AbstractController
{
    #[Route('/clothes/categories', name: 'app_clothes_categories', methods: ['GET'])]
    public function list(Request $request, BreadscrumbsService $breadcrumbs, CategoryViewFactory $views): Response
    {
        return $this->render('clothes/categories/index.html.twig', [
            'breadscrumbs' => $breadcrumbs->resolve(
                (string) $request->attributes->get('_route'),
            ),
            'tabs' => [
                [
                    'id' => 'create',
                    'label' => 'Creer une categorie',
                    'href' => $this->generateUrl('app_clothes_categories_create_modal'),
                    'isActive' => false,
                    'attr' => ['data-turbo-stream' => 'true'],
                ],
                [
                    'id' => 'schedule-online',
                    'label' => 'Programmer une mise en ligne',
                    'href' => '#',
                    'isActive' => false,
                ],
            ],
            'table' => $views->table(
                (string) $request->query->get('search', ''),
                (string) $request->query->get('sort', 'name'),
                (string) $request->query->get('direction', 'asc'),
            ),
        ]);
    }
}
