<?php

namespace App\Controller\Clothes\Category;

use App\Application\Clothes\Guard\Category\CategoryOnlineGuard;
use App\Application\Clothes\Guard\Collection\CollectionOnlineGuard;
use App\Entity\Category\Category;
use App\Service\BreadscrumbsService;
use App\UI\Clothes\Category\CategoryViewFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class ShowCategoryController extends AbstractController
{
    #[Route(
        '/clothes/categories/{id}',
        name: 'app_clothe_category_show',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
    )]
    public function show(
        Category $category,
        BreadscrumbsService $breadcrumbs,
        CategoryOnlineGuard $categoryGuard,
        CollectionOnlineGuard $collectionGuard,
        CategoryViewFactory $views,
        CsrfTokenManagerInterface $csrf,
    ): Response {
        $id = (int) $category->getId();
        $validation = $categoryGuard->canPublish($category);

        return $this->render('clothes/categories/show.html.twig', [
            'breadscrumbs' => $breadcrumbs->resolve(
                'app_clothe_category_show',
                ['id' => $id],
                (string) $category->getName(),
            ),
            'tabs' => [
                [
                    'id' => 'back',
                    'label' => 'Retour',
                    'href' => $this->generateUrl('app_clothes_categories'),
                    'isActive' => false,
                ],
                [
                    'id' => 'edit',
                    'label' => 'Modifier',
                    'href' => $this->generateUrl('app_clothe_category_edit_modal', ['id' => $id]),
                    'isActive' => false,
                    'attr' => ['data-turbo-stream' => 'true'],
                ],
                ['id' => 'delete', 'label' => 'Supprimer', 'href' => '#', 'isActive' => false],
            ],
            'category' => $category,
            'onlineToggle' => $views->onlineToggle($category),
            'imageUploadAction' => $this->generateUrl('app_clothe_category_image_update', ['id' => $id]),
            'imageUploadToken' => $csrf->getToken('category_image_' . $id)->getValue(),
            'publicationRequirements' => $validation->getChecks(),
            'canPublish' => $validation->canPublish(),
            'collectionPublicationStates' => $views->collectionPublicationStates($category, $collectionGuard),
        ]);
    }
}
