<?php

namespace App\Controller\Clothes\Clothe\Details;

use App\Application\Clothes\Mapper\ClotheDetailsMapper;
use App\Application\Clothes\Services\Clothe\ClotheCompletenessChecker;
use App\Application\Clothes\Services\Clothe\ClotheService;
use App\Application\Clothes\Services\Clothe\ClotheSizeGuideService;
use App\Application\Config\Service\SiteTimezone;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Service\BreadscrumbsService;
use App\UI\Tabs\TabsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShowClotheController extends AbstractController
{
    #[Route('/clothes/{slug}', name: 'app_clothes_show', methods: ['GET'])]
    public function show(
        string $slug,
        Request $request,
        BreadscrumbsService $breadscrumbs,
        TabsService $tabsService,
        ClotheService $clotheService,
        ClotheSizeGuideService $sizeGuideService,
        ClotheDetailsMapper $detailsMapper,
        ClotheCompletenessChecker $completenessChecker,
        SiteTimezone $siteTimezone,
    ): Response {
        $variants = $clotheService->getClotheVariantsBySlug($slug);

        if ([] === $variants) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        $clothe = $this->resolveMainClothe($variants);
        $publicationValidation = $completenessChecker->check($clothe);
        $hasPublishableVariant = $clotheService
            ->hasPublishableVariant($variants);

        return $this->render('clothes/show.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve(
                route: (string) $request->attributes->get('_route'),
                routeParams: ['slug' => $slug],
                currentLabel: (string) $clothe->getName(),
            ),
            'tabs' => $tabsService->create([
                'hasPublishableVariant' => $hasPublishableVariant,
            ]),
            'clothe' => $detailsMapper->map(
                clothe: $clothe,
                variants: $variants,
                sizeGuide: $sizeGuideService->buildView($clothe, $variants),
            ),
            'canPublish' => $publicationValidation->isComplete(),
            'publicationErrors' => $publicationValidation->errors(),
            'siteTimezone' => $siteTimezone->name(),
            'variantWorkflows' => $clotheService
                ->getVariantWorkflows($variants),
        ]);
    }

    /**
     * @param list<ClothesVariant> $variants
     */
    private function resolveMainClothe(array $variants): Clothes
    {
        $clothe = $variants[0]?->getClothes();

        if (!$clothe instanceof Clothes) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        return $clothe;
    }
}
