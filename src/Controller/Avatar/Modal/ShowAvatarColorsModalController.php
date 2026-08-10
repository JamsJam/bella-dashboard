<?php

namespace App\Controller\Avatar\Modal;

use App\Application\Avatar\Services\AvatarColorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShowAvatarColorsModalController extends AbstractController
{
    #[Route(
        '/avatar/colors/modal/{type}',
        name: 'app_avatar_colors_modal',
        defaults: ['type' => 'skin'],
        requirements: ['type' => 'skin|hair|eyes|eyebrows|mouth'],
        methods: ['GET'],
    )]
    public function show(AvatarColorService $avatarColorService, string $type = 'skin'): Response
    {
        $html = $this->renderView('avatar/_colors_modal.html.twig', [
            'modal' => $avatarColorService->getModal($type),
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }
}
