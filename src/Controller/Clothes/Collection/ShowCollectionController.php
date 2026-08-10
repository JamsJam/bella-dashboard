<?php

namespace App\Controller\Clothes\Collection;

use App\Application\Clothes\Guard\Collection\CollectionOnlineGuard;
use App\Application\Clothes\Mapper\CollectionClothesMapper;
use App\Entity\Collections\Collections;
use App\Service\BreadscrumbsService;
use App\UI\Clothes\Collection\CollectionViewFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShowCollectionController extends AbstractController
{
    #[Route('/collections/{id}', name: 'app_clothes_collection', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        Collections $collection,
        CollectionOnlineGuard $onlineGuard,
        CollectionClothesMapper $clothesMapper,
        CollectionViewFactory $viewFactory,
        BreadscrumbsService $breadscrumbs,
    ): Response {
        $publicationValidation = $onlineGuard->canPublish($collection);

        return $this->render('clothes/collections/show.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve(
                'app_clothes_collection',
                ['id' => $collection->getId()],
                (string) $collection->getName(),
            ),
            'tabs' => [['id' => 'back', 'label' => 'Retour', 'href' => $this->generateUrl('app_clothe_collection'), 'isActive' => false]],
            'collection' => $collection,
            'clothes' => $clothesMapper->map($collection),
            'onlineToggle' => $viewFactory->onlineToggle($collection),
            'canPublish' => $publicationValidation->canPublish(),
            'publicationErrors' => $publicationValidation->getErrors(),
        ]);
    }
}
