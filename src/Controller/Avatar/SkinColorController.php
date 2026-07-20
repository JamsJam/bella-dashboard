<?php

namespace App\Controller\Avatar;

use App\Entity\Avatar\Skincolor;
use App\Repository\Avatar\SkincolorRepository;
use App\Service\LoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class SkinColorController extends AbstractController
{
    #[Route('/avatar/skin-colors/modal', name: 'app_avatar_skin_colors_modal', methods: ['GET'])]
    public function modal(
        SkincolorRepository $skinColorRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        return $this->renderModal($skinColorRepository, $csrfTokenManager);
    }

    #[Route('/avatar/skin-colors/{id}/delete', name: 'app_avatar_skin_color_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(
        Skincolor $skinColor,
        Request $request,
        EntityManagerInterface $entityManager,
        SkincolorRepository $skinColorRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
        LoggerService $logger,
    ): Response {
        $token = new CsrfToken(
            $this->csrfTokenId($skinColor),
            (string) $request->request->get('_csrf_token', ''),
        );

        if (!$csrfTokenManager->isTokenValid($token)) {
            $logger->warning('Invalid CSRF token for avatar skin color deletion.', [
                'skin_color_id' => $skinColor->getId(),
            ]);

            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        $associatedCount = $this->associatedCount($skinColor);

        foreach ($skinColor->getNoses()->toArray() as $nose) {
            $entityManager->remove($nose);
        }

        foreach ($skinColor->getBodies()->toArray() as $body) {
            $entityManager->remove($body);
        }

        foreach ($skinColor->getFaces()->toArray() as $face) {
            $entityManager->remove($face);
        }

        $skinColorId = $skinColor->getId();
        $entityManager->remove($skinColor);
        $entityManager->flush();

        $logger->info('Avatar skin color deleted.', [
            'skin_color_id' => $skinColorId,
            'associated_elements_deleted' => $associatedCount,
        ]);

        return $this->renderModal($skinColorRepository, $csrfTokenManager);
    }

    private function renderModal(
        SkincolorRepository $skinColorRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $skinColors = [];

        foreach ($skinColorRepository->findBy([], ['name' => 'ASC']) as $skinColor) {
            $skinColors[] = [
                'entity' => $skinColor,
                'associatedCount' => $this->associatedCount($skinColor),
                'deleteUrl' => $this->generateUrl('app_avatar_skin_color_delete', ['id' => $skinColor->getId()]),
                'csrfToken' => $csrfTokenManager->getToken($this->csrfTokenId($skinColor))->getValue(),
            ];
        }

        $html = $this->renderView('avatar/_skin_colors_modal.html.twig', [
            'skinColors' => $skinColors,
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    private function associatedCount(Skincolor $skinColor): int
    {
        return $skinColor->getNoses()->count()
            + $skinColor->getBodies()->count()
            + $skinColor->getFaces()->count();
    }

    private function csrfTokenId(Skincolor $skinColor): string
    {
        return 'avatar_skin_color_delete_'.(string) $skinColor->getId();
    }
}
