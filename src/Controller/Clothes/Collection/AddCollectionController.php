<?php

namespace App\Controller\Clothes\Collection;

use App\Application\Clothes\Provider\CollectionProvider\CollectionProvider;
use App\Application\Clothes\Services\Clothe\ClothesCreationService;
use App\Application\Clothes\Services\Clothe\ClotheService;
use App\Application\Clothes\Services\Collection\CollectionCreationService;
use App\Notifier\Services\FlashService;
use App\Service\BreadscrumbsService;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AddCollectionController extends AbstractController
{
    #[Route('/collections/add', name: 'app_clothe_collection_add', methods: ['GET', 'POST'])]
    public function add(
        Request $request,
        CollectionProvider $provider,
        CollectionCreationService $collectionCreationService,
        ClothesCreationService $clothesCreationService,
        LoggerService $logger,
        FlashService $flashService,
        BreadscrumbsService $breadscrumbs,
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('collection_create', (string) $request->getPayload()->get('_token'))) {
                $flashService->error('Token CSRF invalide.');
                $logger->warning('Invalid CSRF token for collection creation.');

                return $this->redirectToRoute('app_clothe_collection_add');
            }

            try {
                $collection = $collectionCreationService->createFromRequest($request);
                $clothesCreationService->createForCollectionFromRequest($request, $collection);
            } catch (\InvalidArgumentException $exception) {
                $flashService->error($exception->getMessage());
                $logger->warning('Collection creation rejected.', ['error' => $exception->getMessage()]);

                return $this->redirectToRoute('app_clothe_collection_add');
            }

            $flashService->success('Collection creee hors-ligne.');
            $logger->info('Collection created.', ['collection_id' => $collection->getId(), 'collection_name' => $collection->getName()]);

            return $this->redirectToRoute('app_clothe_collection');
        }

        return $this->render('clothes/collections/add.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve(
                (string) $request->attributes->get('_route'),
                currentLabel: 'Ajouter une collection',
            ),
            'tabs' => [['id' => 'back', 'label' => 'Retour', 'href' => $this->generateUrl('app_clothe_collection'), 'isActive' => false]],
            'action' => $this->generateUrl('app_clothe_collection_add'),
            'categories' => $provider->categories(),
            'colors' => $provider->colors(),
            'availableSizes' => ClotheService::AVAILABLE_SIZES,
        ]);
    }
}
