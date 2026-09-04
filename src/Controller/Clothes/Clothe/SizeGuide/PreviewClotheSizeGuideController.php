<?php

namespace App\Controller\Clothes\Clothe\SizeGuide;

use App\Application\Clothes\Services\Clothe\ClotheService;
use App\Application\Clothes\Services\Clothe\ClotheSizeGuideService;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PreviewClotheSizeGuideController extends AbstractController
{
    #[Route('/clothes/{slug}/size-guide/preview', name: 'app_clothes_size_guide_preview', methods: ['POST'])]
    public function previewSizeGuide(
        string $slug,
        Request $request,
        ClotheService $clotheService,
        ClotheSizeGuideService $clotheSizeGuideService,
    ): Response {
        $variants = $clotheService->getClotheVariantsBySlug($slug);
        if ([] === $variants) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        $mainClothe = $this->resolveMainClothe($variants);
        $measurements = $request->request->all('measurements');
        $measurementTypes = $request->request->all('measurement_types');
        $sizeGuide = $clotheSizeGuideService->buildPreviewView(
            mainClothe: $mainClothe,
            variants: $variants,
            selectedTypeUuids: is_array($measurementTypes) ? $measurementTypes : [],
            submittedMeasurements: is_array($measurements) ? $measurements : [],
        );

        $html = $this->renderView('clothes/_size_guide_table.html.twig', [
            'sizeGuide' => $sizeGuide,
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="clothe-size-guide-table"><template>%s</template></turbo-stream>', $html),
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
