<?php

namespace App\Controller\Clothes\Clothe\Modal;

use App\Repository\Clothes\ClothesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class FeaturedClothesModalController extends AbstractController
{
    #[Route('/clothes/featured/modal', name: 'app_clothes_featured_modal', methods: ['GET'])]
    public function featuredModal(
        ClothesRepository $clothesRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $html = $this->renderView('clothes/_featured_modal.html.twig', [
            'action' => $this->generateUrl('app_clothes_featured_update'),
            'csrfToken' => $csrfTokenManager->getToken('clothe_featured')->getValue(),
            'featuredClothes' => $clothesRepository->findDistinctFeaturedEntities(),
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }
}
