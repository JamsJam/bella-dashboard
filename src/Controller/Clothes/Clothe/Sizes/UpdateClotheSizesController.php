<?php

namespace App\Controller\Clothes\Clothe\Sizes;

use App\Application\Clothes\Services\Clothe\ClotheService;
use App\Notifier\Services\FlashService;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class UpdateClotheSizesController extends AbstractController
{
    #[Route('/clothes/{slug}/sizes', name: 'app_clothes_sizes_update', methods: ['POST'])]
    public function updateSizes(
        string $slug,
        Request $request,
        ClotheService $clotheService,
        FlashService $flashService,
        LoggerService $logger,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('clothe_sizes_' . $slug, (string) $request->request->get('_csrf_token'))) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for clothe sizes update.', [
                'slug' => $slug,
            ]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        $selectedSizes = $request->request->all('sizes');
        $stocks = $request->request->all('stocks');
        $confirmDelete = $request->request->getBoolean('confirm_delete');

        try {
            $clotheService->syncClotheSizes(
                $slug,
                is_array($selectedSizes) ? $selectedSizes : [],
                is_array($stocks) ? $stocks : [],
                $confirmDelete,
            );
            $flashService->success('Tailles et stocks mis a jour.');
            $logger->info('Clothe sizes updated.', [
                'slug' => $slug,
            ]);
        } catch (\InvalidArgumentException $exception) {
            $flashService->error($exception->getMessage());
            $logger->warning('Invalid clothe size stock.', [
                'slug' => $slug,
                'error' => $exception->getMessage(),
            ]);
        } catch (\RuntimeException $exception) {
            if ('delete_confirmation_required' === $exception->getMessage()) {
                $flashService->error('Confirme la suppression des tailles retirees avant de valider.');
                $logger->warning('Clothe size update requires delete confirmation.', [
                    'slug' => $slug,
                ]);
            } else {
                $flashService->error('Impossible de modifier les tailles.');
                $logger->exception($exception, 'Unable to update clothe sizes.', [
                    'slug' => $slug,
                ]);
            }
        }

        return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
    }
}
