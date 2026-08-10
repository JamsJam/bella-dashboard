<?php

namespace App\Controller\Clothes\Clothe\SizeGuide;

use App\Application\Clothes\Services\Clothe\ClotheService;
use App\Application\Clothes\Services\Clothe\ClotheSizeGuideService;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Notifier\Services\FlashService;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class UpdateClotheSizeGuideController extends AbstractController
{
    #[Route('/clothes/{slug}/size-guide', name: 'app_clothes_size_guide_update', methods: ['POST'])]
    public function updateSizeGuide(
        string $slug,
        Request $request,
        ClotheService $clotheService,
        ClotheSizeGuideService $clotheSizeGuideService,
        FlashService $flashService,
        LoggerService $logger,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('clothe_size_guide_' . $slug, (string) $request->request->get('_csrf_token'))) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for clothe size guide update.', [
                'slug' => $slug,
            ]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        $variants = $clotheService->getClotheVariantsBySlug($slug);
        if ([] === $variants) {
            $logger->warning('Clothe not found for size guide update.', [
                'slug' => $slug,
            ]);

            throw $this->createNotFoundException('Clothe not found.');
        }

        $mainClothe = $this->resolveMainClothe($variants);
        $measurements = $request->request->all('measurements');
        $measurementTypes = $request->request->all('measurement_types');

        $clotheSizeGuideService->syncGuide(
            mainClothe: $mainClothe,
            variants: $variants,
            measurements: is_array($measurements) ? $measurements : [],
            selectedTypeUuids: is_array($measurementTypes) ? $measurementTypes : [],
        );

        $flashService->success('Guide des tailles mis a jour.');
        $logger->info('Clothe size guide updated.', [
            'slug' => $slug,
        ]);

        return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
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
