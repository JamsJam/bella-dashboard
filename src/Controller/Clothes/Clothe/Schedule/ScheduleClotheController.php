<?php

namespace App\Controller\Clothes\Clothe\Schedule;

use App\Application\Clothes\Services\Clothe\ClotheService;
use App\Application\Clothes\Services\Clothe\ClotheWorkflowService;
use App\Application\Config\Service\SiteTimezone;
use App\Entity\Clothes\ClothesVariant;
use App\Enum\ClotheStatus;
use App\Notifier\Services\FlashService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ScheduleClotheController extends AbstractController
{
    #[Route('/clothes/{slug}/schedule', name: 'app_clothes_schedule', methods: ['POST'])]
    public function schedule(
        string $slug,
        Request $request,
        ClotheService $clotheService,
        ClotheWorkflowService $workflowService,
        FlashService $flashService,
        SiteTimezone $siteTimezone,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('clothe_schedule_' . $slug, (string) $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $variants = $clotheService->getClotheVariantsBySlug($slug);
        if ([] === $variants) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        try {
            $requestedVariantIds = array_values(array_unique(array_filter(array_map(
                static fn (mixed $id): int => filter_var($id, FILTER_VALIDATE_INT) ?: 0,
                $request->request->all('variantIds'),
            ))));
            if ([] === $requestedVariantIds) {
                throw new \DomainException('Sélectionnez au moins une variante à programmer.');
            }

            $variantsById = [];
            foreach ($variants as $variant) {
                if (null !== $variant->getId()) {
                    $variantsById[$variant->getId()] = $variant;
                }
            }

            $selectedVariants = [];
            foreach ($requestedVariantIds as $variantId) {
                $variant = $variantsById[$variantId] ?? null;
                if (!$variant instanceof ClothesVariant || ClotheStatus::Publishable !== $variant->getPublicationStatus()) {
                    throw new \DomainException('La sélection contient une variante qui ne peut pas être programmée.');
                }
                $selectedVariants[] = $variant;
            }

            $value = trim((string) $request->request->get('scheduledAt'));
            $scheduledAt = '' === $value ? null : $siteTimezone->localInputToUtc($value);
            if (!$scheduledAt instanceof \DateTimeImmutable) {
                throw new \DomainException('Choisissez une date de publication.');
            }

            $workflowService->scheduleAll($selectedVariants, $scheduledAt);
            $flashService->success(sprintf('%d variante(s) programmée(s) pour le %s.', count($selectedVariants), $scheduledAt->format('d/m/Y à H:i')));
        } catch (\Throwable $exception) {
            $flashService->error($exception->getMessage());
        }

        return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
    }
}
