<?php

namespace App\Controller\Clothes\Bestseller;

use App\Application\Clothes\Mapper\BestsellerRequestMapper;
use App\Application\Clothes\Mapper\BestsellerResultMapper;
use App\Application\Clothes\Services\Bestseller\BestsellerUpdateService;
use App\Application\Clothes\Services\Bestseller\BestsellerViewService;
use App\Notifier\Services\FlashService;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UpdateClothesBestsellersController extends AbstractController
{
    #[Route('/clothes/bestsellers', name: 'app_clothes_bestsellers_update', methods: ['POST'])]
    public function update(
        Request $request,
        BestsellerRequestMapper $requestMapper,
        BestsellerUpdateService $updateService,
        BestsellerResultMapper $resultMapper,
        BestsellerViewService $viewService,
        FlashService $flashService,
        LoggerService $logger,
    ): Response {
        $input = $requestMapper->map($request);

        if (!$this->isCsrfTokenValid('clothe_bestseller', $input->csrfToken)) {
            $logger->warning('Invalid CSRF token for bestseller update.');

            return $this->json(['success' => false, 'error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $result = $updateService->update($input);

        if ($result->requiresPruning) {
            $logger->warning('Bestseller update requires pruning.', [
                'mode' => $input->mode,
                'max_items' => $result->maxItems,
                'overflow_count' => count($result->overflow),
            ]);

            if ($input->wantsTurboStream) {
                $html = $this->renderView('clothes/_bestseller_modal.html.twig', [
                    'modal' => $viewService->modal(
                        array_merge($result->bestsellers, $result->overflow),
                        $result->message . ' Decoche les elements a supprimer avant d enregistrer.',
                    ),
                ]);

                return new Response(
                    sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    ['Content-Type' => 'text/vnd.turbo-stream.html'],
                );
            }

            return $this->json($resultMapper->map($result), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($result->success) {
            $flashService->success($result->message);
            $logger->info('Bestseller list updated.', [
                'mode' => $input->mode,
                'items_count' => count($result->bestsellers),
            ]);
        }

        if ($input->wantsTurboStream && !$input->isXmlHttpRequest) {
            return new Response(
                '<turbo-stream action="update" target="modal-root"><template></template></turbo-stream>',
                Response::HTTP_OK,
                ['Content-Type' => 'text/vnd.turbo-stream.html'],
            );
        }

        if ($input->wantsJson) {
            return $this->json($resultMapper->map($result));
        }

        return $this->redirectToRoute('app_clothes');
    }
}
