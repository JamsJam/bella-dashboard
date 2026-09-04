<?php

namespace App\Controller\Clothes\Clothe\Delete;

use App\Application\Clothes\Services\Clothe\ClotheArchiveService;
use App\Entity\Clothes\Clothes;
use App\Notifier\Services\FlashService;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ConfirmDeleteClotheController extends AbstractController
{
    #[Route('/clothes/{id}/delete', name: 'app_clothes_delete_confirm', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function confirmDelete(
        Clothes $clothe,
        Request $request,
        ClotheArchiveService $archiveService,
        FlashService $flashService,
        LoggerService $logger,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('clothe_delete_' . $clothe->getId(), (string) $request->request->get('_csrf_token'))) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for clothe deletion.', [
                'clothe_id' => $clothe->getId(),
            ]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $clothe->getSlug()]);
        }

        try {
            $archiveService->archive($clothe);
            $flashService->success('Vêtement archivé.');
        } catch (\DomainException $exception) {
            $flashService->error($exception->getMessage());
        }

        return $this->redirectToRoute('app_clothes');
    }

}
