<?php

namespace App\Controller\Avatar\Modal;

use App\Application\Avatar\Services\AvatarAttributeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShowAvatarAttributesModalController extends AbstractController
{
    #[Route('/avatar/manage/{group}/{type}', name: 'app_avatar_attributes_modal', requirements: ['group' => 'shapes|sizes'], methods: ['GET'])]
    public function show(string $group, string $type, AvatarAttributeService $service): Response
    {
        $html = $this->renderView('avatar/_attributes_modal.html.twig', ['modal' => $service->modal($group, $type)]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            headers: ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    #[Route('/avatar/manage/{group}/{type}/{id}/delete', name: 'app_avatar_attribute_delete', requirements: ['group' => 'shapes|sizes', 'id' => '\d+'], methods: ['POST'])]
    public function delete(string $group, string $type, int $id, Request $request, AvatarAttributeService $service): Response
    {
        if (!$this->isCsrfTokenValid(sprintf('avatar_attribute_delete_%s_%s_%d', $group, $type, $id), (string) $request->request->get('_csrf_token'))) {
            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        $service->delete($group, $type, $id);

        return $this->show($group, $type, $service);
    }
}
