<?php

namespace App\Controller\Clothes\Color\Modal;

use App\Application\Clothes\Services\Color\ClotheColorModalService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShowClothesColorsModalController extends AbstractController
{
    #[Route('/clothes/colors/modal', name: 'app_clothes_colors_modal', methods: ['GET'])]
    public function show(ClotheColorModalService $modalService): Response
    {
        $html = $this->renderView('clothes/_colors_modal.html.twig', [
            'modal' => $modalService->getModal(),
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }
}
