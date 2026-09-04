<?php

namespace App\Controller\Clothes\Clothe\Modal;

use App\Application\Clothes\Services\Clothe\ClotheService;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class HighlightImageClotheModalController extends AbstractController
{
    #[Route('/clothes/{slug}/highlight-image/{slot}/modal', name: 'app_clothes_highlight_image_modal', requirements: ['slot' => 'carousel|bestseller'], methods: ['GET'])]
    public function highlightImageModal(
        string $slug,
        string $slot,
        ClotheService $clotheService,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $variants = $clotheService->getClotheVariantsBySlug($slug);

        if ([] === $variants) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        $mainClothe = $this->resolveMainClothe($variants);
        $images = [];
        $selectedImage = null;

        foreach ($variants as $variant) {
            if (!$variant instanceof ClothesVariant) {
                continue;
            }

            $images = array_merge($images, $variant->getImages() ?? []);
            $selectedImage ??= 'carousel' === $slot
                ? $variant->getHighlightImage()
                : $variant->getBestsellerImage();
        }

        $html = $this->renderView('clothes/_highlight_image_modal.html.twig', [
            'slot' => $slot,
            'slotLabel' => 'carousel' === $slot ? 'carrousel' : 'bestseller',
            'clotheName' => $mainClothe->getName(),
            'images' => array_values(array_unique(array_filter($images))),
            'selectedImage' => $selectedImage,
            'action' => $this->generateUrl('app_clothes_highlight_image_update', ['slug' => $slug, 'slot' => $slot]),
            'csrfToken' => $csrfTokenManager->getToken('clothe_highlight_image_' . $slug . '_' . $slot)->getValue(),
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    private function resolveMainClothe(array $variants): Clothes
    {
        $firstVariant = $variants[0] ?? null;
        $clothe = $firstVariant instanceof ClothesVariant ? $firstVariant->getClothes() : null;

        if (!$clothe instanceof Clothes) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        return $clothe;
    }
}
