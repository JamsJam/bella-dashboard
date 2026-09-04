<?php

namespace App\Controller\Clothes\Clothe\Modal;

use App\Application\Clothes\Services\Clothe\ClotheService;
use App\Application\Config\Service\SiteTimezone;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Enum\ClotheStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class ScheduleClotheModalController extends AbstractController
{
    #[Route('/clothes/{slug}/schedule/modal', name: 'app_clothes_schedule_modal', methods: ['GET'])]
    public function scheduleModal(
        string $slug,
        ClotheService $clotheService,
        CsrfTokenManagerInterface $csrfTokenManager,
        SiteTimezone $siteTimezone,
    ): Response {
        $variants = $clotheService->getClotheVariantsBySlug($slug);
        if ([] === $variants) {
            throw $this->createNotFoundException('Clothe not found.');
        }
        $publishableVariants = array_values(array_filter(
            $variants,
            static fn (ClothesVariant $variant): bool => ClotheStatus::Publishable === $variant->getPublicationStatus(),
        ));
        if ([] === $publishableVariants) {
            throw $this->createAccessDeniedException('Aucune variante publiable.');
        }

        $html = $this->renderView('clothes/_schedule_modal.html.twig', [
            'action' => $this->generateUrl('app_clothes_schedule', ['slug' => $slug]),
            'csrfToken' => $csrfTokenManager->getToken('clothe_schedule_' . $slug)->getValue(),
            'clotheName' => $this->resolveMainClothe($variants)->getName(),
            'variants' => $publishableVariants,
            'minimumDate' => $siteTimezone->nowLocal()->modify('+1 minute')->format('Y-m-d\TH:i'),
            'siteTimezone' => $siteTimezone->name(),
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
