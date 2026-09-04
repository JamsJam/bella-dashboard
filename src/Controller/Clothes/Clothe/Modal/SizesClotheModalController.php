<?php

namespace App\Controller\Clothes\Clothe\Modal;

use App\Application\Clothes\Services\Clothe\ClotheService;
use App\Entity\Clothes\ClothesVariant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class SizesClotheModalController extends AbstractController
{
    #[Route('/clothes/{slug}/sizes/modal', name: 'app_clothes_sizes_modal', methods: ['GET'])]
    public function sizesModal(
        string $slug,
        ClotheService $clotheService,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $variants = $clotheService->getClotheSizeVariantsBySlug($slug);

        if ([] === $variants) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        $selectedSizes = array_values(array_unique(array_filter(array_map(
            fn (ClothesVariant $variant): ?string => $variant->getSize()?->getName(),
            $variants,
        ))));
        $stocks = [];
        foreach ($variants as $variant) {
            $sizeName = $variant->getSize()?->getName();
            if (null !== $sizeName) {
                $stocks[$sizeName] = $variant->getStock();
            }
        }

        $html = $this->renderView('clothes/_sizes_modal.html.twig', [
            'slug' => $slug,
            'availableSizes' => ClotheService::AVAILABLE_SIZES,
            'selectedSizes' => $selectedSizes,
            'stocks' => $stocks,
            'action' => $this->generateUrl('app_clothes_sizes_update', ['slug' => $slug]),
            'csrfToken' => $csrfTokenManager->getToken('clothe_sizes_' . $slug)->getValue(),
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }
}
