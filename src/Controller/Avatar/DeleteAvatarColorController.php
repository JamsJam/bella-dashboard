<?php

namespace App\Controller\Avatar;

use App\Application\Avatar\Exception\AvatarColorNotFoundException;
use App\Application\Avatar\Services\AvatarColorService;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteAvatarColorController extends AbstractController
{
    #[Route(
        '/avatar/colors/{type}/{id}/delete',
        name: 'app_avatar_color_delete',
        requirements: ['type' => 'skin|hair|eyes|eyebrows|mouth', 'id' => '\d+'],
        methods: ['POST'],
    )]
    public function delete(
        string $type,
        int $id,
        Request $request,
        AvatarColorService $avatarColorService,
        LoggerService $logger,
    ): Response {
        if (!$this->isCsrfTokenValid($this->csrfTokenId($type, $id), (string) $request->request->get('_csrf_token', ''))) {
            $logger->warning('Invalid CSRF token for avatar color deletion.', [
                'color_type' => $type,
                'color_id' => $id,
            ]);

            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        try {
            $associatedElementsDeleted = $avatarColorService->delete($type, $id);
        } catch (AvatarColorNotFoundException) {
            throw $this->createNotFoundException('Couleur d’avatar introuvable.');
        }

        $logger->info('Avatar color deleted.', [
            'color_type' => $type,
            'color_id' => $id,
            'associated_elements_deleted' => $associatedElementsDeleted,
        ]);

        $html = $this->renderView('avatar/_colors_modal.html.twig', [
            'modal' => $avatarColorService->getModal($type),
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    private function csrfTokenId(string $type, int $id): string
    {
        return sprintf('avatar_color_delete_%s_%d', $type, $id);
    }
}
