<?php

namespace App\Controller\Clothes;

use App\Application\Clothes\Services\ClotheColorDeletionService;
use App\Entity\Clothes\Clothescolor;
use App\Service\LoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class ClothesColorController extends AbstractController
{
    #[Route('/clothes/colors/modal', name: 'app_clothes_colors_modal', methods: ['GET'])]
    public function modal(
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        return $this->renderModal($entityManager, $csrfTokenManager);
    }

    #[Route(
        '/clothes/colors/{id}/delete',
        name: 'app_clothes_color_delete',
        requirements: ['id' => '\\d+'],
        methods: ['POST'],
    )]
    public function delete(
        Clothescolor $color,
        Request $request,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        ClotheColorDeletionService $deletionService,
        LoggerService $logger,
    ): Response {
        $token = new CsrfToken(
            $this->csrfTokenId((int) $color->getId()),
            (string) $request->request->get('_csrf_token', ''),
        );
        if (!$csrfTokenManager->isTokenValid($token)) {
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

        return $this->renderModal($entityManager, $csrfTokenManager);
    }

    private function renderModal(
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $colors = [];
        foreach ($entityManager->getRepository(Clothescolor::class)->findBy([], ['name' => 'ASC']) as $color) {
            $id = $color->getId();
            if (null === $id) {
                continue;
            }

            $colors[] = [
                'name' => (string) $color->getName(),
                'hexa' => $color->getHexa(),
                'clothesCount' => $color->getClothes()->count(),
                'variantsCount' => $color->getVariants()->count(),
                'deleteUrl' => $this->generateUrl('app_clothes_color_delete', ['id' => $id]),
                'csrfToken' => $csrfTokenManager->getToken($this->csrfTokenId($id))->getValue(),
            ];
        }

        $html = $this->renderView('clothes/_colors_modal.html.twig', ['colors' => $colors]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    private function csrfTokenId(int $id): string
    {
        return sprintf('clothes_color_delete_%d', $id);
    }
}
