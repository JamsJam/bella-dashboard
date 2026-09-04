<?php

namespace App\Controller\Clothes\Collection;

use App\Application\Clothes\Services\Collection\CollectionPublicationService;
use App\Entity\Collections\Collections;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ToggleCollectionOnlineController extends AbstractController
{
    #[Route('/collections/{id}/online/{state}', name: 'app_clothes_collection_toggle_online', requirements: ['id' => '\d+', 'state' => 'on|off'], methods: ['POST'])]
    public function toggle(
        Collections $collection,
        string $state,
        Request $request,
        CollectionPublicationService $publicationService,
        LoggerService $logger,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('collection_online_' . $collection->getId(), (string) $request->headers->get('X-CSRF-TOKEN', ''))) {
            $logger->warning('Invalid CSRF token for collection online toggle.', ['collection_id' => $collection->getId(), 'state' => $state]);

            return $this->json(['success' => false, 'error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        if ('on' === $state && !$publicationService->publish($collection)) {
            $logger->warning('Collection publication rejected.', ['collection_id' => $collection->getId()]);

            return $this->json(['success' => false, 'error' => 'Collection cannot be published.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ('off' === $state) {
            $publicationService->unpublish($collection);
        }

        return $this->json([
            'success' => true,
            'isOnline' => $collection->isOnline(),
            'clothesFrameUrl' => $this->generateUrl('app_clothes_collection_clothes', ['id' => $collection->getId()]),
        ]);
    }
}
