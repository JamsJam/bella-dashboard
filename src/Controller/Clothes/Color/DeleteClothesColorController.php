<?php

namespace App\Controller\Clothes\Color;

use App\Application\Clothes\Services\Color\ClotheColorDeletionService;
use App\Application\Clothes\Services\Color\ClotheColorModalService;
use App\Entity\Clothes\Clothescolor;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteClothesColorController extends AbstractController
{
    #[Route(
        '/clothes/colors/{id}/delete',
        name: 'app_clothes_color_delete',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    public function delete(
        Clothescolor $color,
        Request $request,
        ClotheColorDeletionService $deletionService,
        ClotheColorModalService $modalService,
        LoggerService $logger,
    ): Response {
        if (
            !$this->isCsrfTokenValid(
                sprintf('clothes_color_delete_%d', $color->getId()),
                (string) $request->request->get('_csrf_token', ''),
            )
        ) {
            $logger->warning('Invalid CSRF token for clothes color deletion.', [
                'color_id' => $color->getId(),
            ]);

            return new Response('Jeton CSRF invalide.', Response::HTTP_FORBIDDEN);
        }

        $colorId = $color->getId();
        $result = $deletionService->delete($color);
        $logger->info('Clothes color deleted.', [
            'color_id' => $colorId,
            'variants_deleted' => $result['variants'],
            'clothes_deleted' => $result['clothes'],
            'images_deleted' => $result['images'],
        ]);

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
