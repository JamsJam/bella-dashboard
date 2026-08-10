<?php

namespace App\Controller\Clothes\Clothe\Modal;

use App\Entity\Clothes\Clothes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class DeleteClotheModalController extends AbstractController
{
    #[Route('/clothes/{id}/delete/modal', name: 'app_clothes_delete_modal', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function deleteModal(
        Clothes $clothe,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $html = $this->renderView('clothes/_delete_modal.html.twig', [
            'clothe' => $clothe,
            'action' => $this->generateUrl('app_clothes_delete_confirm', ['id' => $clothe->getId()]),
            'csrfToken' => $csrfTokenManager->getToken('clothe_delete_' . $clothe->getId())->getValue(),
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }
}
