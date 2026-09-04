<?php

namespace App\Controller\Clothes\Clothe\Modal;

use App\Application\Clothes\Services\Clothe\ClotheService;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class ImagesClotheModalController extends AbstractController
{
    #[Route('/clothes/{slug}/images/modal', name: 'app_clothes_images_modal', methods: ['GET'])]
    public function imagesModal(
        string $slug,
        Request $request,
        ClotheService $clotheService,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $variants = $clotheService->getClotheVariantsBySlug($slug);

        if ([] === $variants) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        $mainClothe = $this->resolveMainClothe($variants);
        $colorId = $request->query->getInt('color') ?: null;
        $imageVariants = $this->filterVariantsByColor($variants, $colorId);
        $images = [];

        foreach ($imageVariants as $variant) {
            if ($variant instanceof ClothesVariant) {
                $images = array_merge($images, $variant->getImages() ?? []);
            }
        }

        $html = $this->renderView('clothes/_images_modal.html.twig', [
            'clotheName' => null !== $colorId && [] !== $imageVariants
                ? sprintf('%s - %s', (string) $mainClothe->getName(), (string) $imageVariants[0]->getColor()?->getName())
                : $mainClothe->getName(),
            'images' => array_values(array_unique(array_filter($images))),
            'action' => $this->generateUrl('app_clothes_images_update', array_filter([
                'slug' => $slug,
                'color' => $colorId,
            ], static fn (mixed $value): bool => null !== $value)),
            'csrfToken' => $csrfTokenManager->getToken('clothe_images_' . $slug)->getValue(),
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

    private function filterVariantsByColor(array $variants, ?int $colorId): array
    {
        if (null === $colorId) {
            return $variants;
        }

        return array_values(array_filter(
            $variants,
            static fn (ClothesVariant $variant): bool => $variant->getColor()?->getId() === $colorId,
        ));
    }
}
