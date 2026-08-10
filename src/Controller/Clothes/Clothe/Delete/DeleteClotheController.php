<?php

namespace App\Controller\Clothes\Clothe\Delete;

use App\Application\Clothes\Services\Clothe\ClotheArchiveService;
use App\Entity\Clothes\Clothes;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteClotheController extends AbstractController
{
    #[Route('/clothes/{id}', name: 'app_clothes_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(
        Clothes $clothe,
        Request $request,
        ClotheArchiveService $archiveService,
        LoggerService $logger,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('clothe_delete', (string) $request->headers->get('X-CSRF-TOKEN', ''))) {
            $logger->warning('Invalid CSRF token for clothe deletion.', [
                'clothe_id' => $clothe->getId(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Invalid CSRF token.',
            ], Response::HTTP_FORBIDDEN);
        }

        $id = $clothe->getId();
        try {
            $archiveService->archive($clothe);
        } catch (\DomainException $exception) {
            return $this->json(['success' => false, 'error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json([
            'success' => true,
            'id' => $id,
        ]);
    }

}
