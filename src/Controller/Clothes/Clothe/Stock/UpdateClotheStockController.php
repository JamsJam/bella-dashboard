<?php

namespace App\Controller\Clothes\Clothe\Stock;

use App\Application\Clothes\Services\Clothe\ClotheService;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use App\Entity\Clothes\ClothesVariant;
use App\Notifier\Services\FlashService;
use App\Service\LoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class UpdateClotheStockController extends AbstractController
{
    #[Route('/clothes/{slug}/stock', name: 'app_clothes_stock_update', methods: ['POST'])]
    public function updateStock(
        string $slug,
        Request $request,
        ClotheService $clotheService,
        EntityManagerInterface $entityManager,
        FlashService $flashService,
        LoggerService $logger,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('clothe_stock_' . $slug, (string) $request->request->get('_csrf_token'))) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for clothe stock update.', [
                'slug' => $slug,
            ]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        $variants = $clotheService->getClotheVariantsBySlug($slug);
        if ([] === $variants) {
            $logger->warning('Clothe not found for stock update.', [
                'slug' => $slug,
            ]);

            throw $this->createNotFoundException('Clothe not found.');
        }

        $submittedStocks = $request->request->all('stocks');
        $submittedColors = $request->request->all('colors');
        $submittedSizes = $request->request->all('sizes');
        $stocks = [];
        $colors = [];
        $sizes = [];
        $mainClothe = $this->resolveMainClothe($variants);

        foreach ($variants as $variant) {
            if (!$variant instanceof ClothesVariant || null === $variant->getId()) {
                continue;
            }

            $variantId = (string) $variant->getId();
            $stock = filter_var($submittedStocks[(string) $variant->getId()] ?? null, FILTER_VALIDATE_INT);
            if (false === $stock || $stock < 0) {
                $flashService->error('Le stock doit etre un entier positif ou nul.');
                $logger->warning('Invalid stock value submitted.', [
                    'slug' => $slug,
                    'clothe_id' => $variant->getId(),
                ]);

                return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
            }

            $stocks[$variant->getId()] = $stock;

            $color = $entityManager->getRepository(Clothescolor::class)->find((int) ($submittedColors[$variantId] ?? 0));
            if (!$color instanceof Clothescolor) {
                $flashService->error('Selectionne une couleur valide pour chaque variante.');

                return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
            }

            $sizeName = trim((string) ($submittedSizes[$variantId] ?? ''));
            if (!in_array($sizeName, ClotheService::AVAILABLE_SIZES, true)) {
                $flashService->error('Selectionne une taille valide pour chaque variante.');

                return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
            }

            $colors[$variant->getId()] = $color;
            $sizes[$variant->getId()] = $this->findOrCreateSize($sizeName, $entityManager);
        }

        $now = new \DateTimeImmutable();
        foreach ($variants as $variant) {
            if (!$variant instanceof ClothesVariant || null === $variant->getId()) {
                continue;
            }

            $stock = $stocks[$variant->getId()];
            $variant
                ->setColor($colors[$variant->getId()])
                ->setSize($sizes[$variant->getId()])
                ->setSku($this->createVariantSku($mainClothe, $colors[$variant->getId()], $sizes[$variant->getId()]))
                ->setStock($stock)
                ->setEditedAt($now);
        }

        try {
            $this->assertUniqueVariantPayload($mainClothe, $entityManager);
        } catch (\InvalidArgumentException $exception) {
            $flashService->error($exception->getMessage());

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        $entityManager->flush();
        $flashService->success('Variantes mises a jour.');
        $logger->info('Clothe stocks updated.', [
            'slug' => $slug,
            'variants_count' => count($stocks),
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

    private function findOrCreateSize(string $sizeName, EntityManagerInterface $entityManager): Clothessize
    {
        $size = $entityManager->getRepository(Clothessize::class)->findOneBy(['name' => $sizeName]);
        if ($size instanceof Clothessize) {
            return $size;
        }

        $size = (new Clothessize())
            ->setName($sizeName)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setEditedAt(new \DateTimeImmutable());

        $entityManager->persist($size);

        return $size;
    }

    private function createVariantSku(Clothes $clothe, ?Clothescolor $color, ?Clothessize $size): string
    {
        $slugger = new AsciiSlugger();

        return strtoupper(sprintf(
            '%s-%s-%s',
            (string) $slugger->slug((string) $clothe->getName()),
            (string) $slugger->slug((string) $color?->getName()),
            (string) $slugger->slug((string) $size?->getName()),
        ));
    }

    private function assertUniqueVariantPayload(Clothes $clothe, ?EntityManagerInterface $entityManager = null): void
    {
        $combinations = [];
        $skus = [];

        foreach ($clothe->getVariants() as $variant) {
            $colorName = (string) $variant->getColor()?->getName();
            $sizeName = (string) $variant->getSize()?->getName();
            $combinationKey = mb_strtolower($colorName . '|' . $sizeName);
            $skuKey = mb_strtolower((string) $variant->getSku());

            if (isset($combinations[$combinationKey])) {
                throw new \InvalidArgumentException(sprintf('Une variante existe deja pour la couleur %s et la taille %s.', $colorName, $sizeName));
            }

            if (isset($skus[$skuKey])) {
                throw new \InvalidArgumentException(sprintf('Le SKU %s est deja utilise.', (string) $variant->getSku()));
            }

            if ($entityManager instanceof EntityManagerInterface) {
                $existingVariant = $entityManager->getRepository(ClothesVariant::class)->findOneBy(['sku' => $variant->getSku()]);
                if ($existingVariant instanceof ClothesVariant && !$clothe->getVariants()->contains($existingVariant)) {
                    throw new \InvalidArgumentException(sprintf('Le SKU %s est deja utilise.', (string) $variant->getSku()));
                }
            }

            $combinations[$combinationKey] = true;
            $skus[$skuKey] = true;
        }
    }
}
