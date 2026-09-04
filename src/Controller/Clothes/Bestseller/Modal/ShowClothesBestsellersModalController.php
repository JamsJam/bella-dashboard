<?php

namespace App\Controller\Clothes\Bestseller\Modal;

use App\Application\Clothes\Services\Bestseller\BestsellerViewService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShowClothesBestsellersModalController extends AbstractController
{
    #[Route('/clothes/bestsellers/modal', name: 'app_clothes_bestsellers_modal', methods: ['GET'])]
    public function show(BestsellerViewService $viewService): Response
    {
        $html = $this->renderView('clothes/_bestseller_modal.html.twig', [
            'modal' => $viewService->modal(),
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }
}
