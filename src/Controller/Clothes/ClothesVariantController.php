<?php

namespace App\Controller\Clothes;

use App\Application\Clothes\Services\ClotheService;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use App\Notifier\Services\FlashService;
use App\Service\LoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class ClothesVariantController extends AbstractController
{
    #[Route('/clothes/variants/{id}/edit/modal', name: 'app_clothes_variant_edit_modal', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function editModal(
        ClothesVariant $variant,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $html = $this->renderView('clothes/variants/_edit_modal.html.twig', [
            'variant' => $variant,
            'isGroup' => false,
            'availableSizes' => ClotheService::AVAILABLE_SIZES,
            'action' => $this->generateUrl('app_clothes_variant_update', ['id' => $variant->getId()]),
            'csrfToken' => $csrfTokenManager->getToken('clothe_variant_edit_'.$variant->getId())->getValue(),
        ]);

        return $this->renderModalStream($html);
    }

    #[Route('/clothes/{slug}/variants/color/{color}/edit/modal', name: 'app_clothes_variant_group_edit_modal', requirements: ['color' => '\d+'], methods: ['GET'])]
    public function editGroupModal(
        string $slug,
        int $color,
        ClotheService $clotheService,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $variants = $this->resolveColorVariants($clotheService->getClotheVariantsBySlug($slug), $color);
        $variant = $variants[0] ?? null;

        if (!$variant instanceof ClothesVariant) {
            throw $this->createNotFoundException('Variant not found.');
        }

        $html = $this->renderView('clothes/variants/_edit_modal.html.twig', [
            'variant' => $variant,
            'isGroup' => true,
            'colors' => $entityManager->getRepository(Clothescolor::class)->findBy([], ['name' => 'ASC']),
            'action' => $this->generateUrl('app_clothes_variant_group_update', ['slug' => $slug, 'color' => $color]),
            'csrfToken' => $csrfTokenManager->getToken('clothe_variant_group_edit_'.$slug.'_'.$color)->getValue(),
        ]);

        return $this->renderModalStream($html);
    }

    #[Route('/clothes/{slug}/variants', name: 'app_clothes_variant_create', methods: ['POST'])]
    public function create(
        string $slug,
        Request $request,
        ClotheService $clotheService,
        EntityManagerInterface $entityManager,
        FlashService $flashService,
        LoggerService $logger,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('clothe_variant_create_'.$slug, (string) $request->request->get('_csrf_token'))) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for clothe variant creation.', ['slug' => $slug]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        $variants = $clotheService->getClotheVariantsBySlug($slug);
        $clothe = $this->resolveMainClothe($variants);

        try {
            $sourceVariant = $variants[0] ?? null;
            if (!$sourceVariant instanceof ClothesVariant || !$sourceVariant->getColor() instanceof Clothescolor) {
                throw new \InvalidArgumentException('Impossible de retrouver la couleur du variant courant.');
            }

            $color = $sourceVariant->getColor();
            $sizes = $this->resolveSizes($request, $entityManager);
            $stock = $this->resolveStock($request);
            $now = new \DateTimeImmutable();
            $sourceImages = $sourceVariant->getImages() ?? [];
            $createdVariants = [];

            foreach ($sizes as $size) {
                $variantName = $this->createVariantName((string) $clothe->getName(), $color, $size);
                $variant = (new ClothesVariant())
                    ->setName($variantName)
                    ->setSlug($this->createVariantSlug((string) $clothe->getName(), $color))
                    ->setColor($color)
                    ->setSize($size)
                    ->setSku($this->createSku($clothe, $color, $size))
                    ->setStock($stock)
                    ->setDescription($sourceVariant->getDescription())
                    ->setMetadescription($sourceVariant->getMetadescription())
                    ->setImages($sourceImages)
                    ->setHighlightImage($sourceVariant->getHighlightImage())
                    ->setBestsellerImage($sourceVariant->getBestsellerImage())
                    ->setIsBestseller($sourceVariant->isBestseller())
                    ->setIsInCarousel($sourceVariant->isInCarousel())
                    ->setIsOnline(false)
                    ->setCreatedAt($now)
                    ->setEditedAt($now);

                $clothe->addVariant($variant);
                $this->assertVariantUnique($variant, $entityManager);
                $createdVariants[] = $variant;
            }

            $clothe->setEditedAt($now);
            $entityManager->flush();
            $flashService->success(count($createdVariants) > 1 ? 'Variantes ajoutees.' : 'Variante ajoutee.');
            $logger->info('Clothe variant created.', [
                'clothe_id' => $clothe->getId(),
                'variants_count' => count($createdVariants),
            ]);
        } catch (\InvalidArgumentException $exception) {
            $flashService->error($exception->getMessage());
            $logger->warning('Clothe variant creation rejected.', [
                'slug' => $slug,
                'error' => $exception->getMessage(),
            ]);
        }

        return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
    }

    #[Route('/clothes/variants/{id}', name: 'app_clothes_variant_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(
        ClothesVariant $variant,
        Request $request,
        EntityManagerInterface $entityManager,
        FlashService $flashService,
        LoggerService $logger,
    ): RedirectResponse {
        $clothe = $this->resolveVariantClothe($variant);
        $slug = (string) $clothe->getSlug();

        if (!$this->isCsrfTokenValid('clothe_variant_edit_'.$variant->getId(), (string) $request->request->get('_csrf_token'))) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for clothe variant update.', ['variant_id' => $variant->getId()]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        try {
            $size = $this->resolveSize((string) $request->request->get('size', ''), $entityManager);
            $stock = $this->resolveStock($request);
            $now = new \DateTimeImmutable();
            $color = $variant->getColor();

            if (!$color instanceof Clothescolor) {
                throw new \InvalidArgumentException('Impossible de retrouver la couleur de cette variante.');
            }

            $variant
                ->setName($this->createVariantName((string) $clothe->getName(), $color, $size))
                ->setSize($size)
                ->setSku($this->createSku($clothe, $color, $size))
                ->setStock($stock)
                ->setIsOnline($stock > 0 && $variant->isOnline())
                ->setEditedAt($now);
            $clothe->setEditedAt($now);

            $this->assertVariantUnique($variant, $entityManager);
            $entityManager->flush();
            $flashService->success('Variante mise a jour.');
            $logger->info('Clothe variant updated.', ['variant_id' => $variant->getId()]);
        } catch (\InvalidArgumentException $exception) {
            $flashService->error($exception->getMessage());
            $logger->warning('Clothe variant update rejected.', [
                'variant_id' => $variant->getId(),
                'error' => $exception->getMessage(),
            ]);
        }

        return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
    }

    #[Route('/clothes/{slug}/variants/color/{color}', name: 'app_clothes_variant_group_update', requirements: ['color' => '\d+'], methods: ['POST'])]
    public function updateGroup(
        string $slug,
        int $color,
        Request $request,
        ClotheService $clotheService,
        EntityManagerInterface $entityManager,
        FlashService $flashService,
        LoggerService $logger,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('clothe_variant_group_edit_'.$slug.'_'.$color, (string) $request->request->get('_csrf_token'))) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for clothe variant group update.', [
                'slug' => $slug,
                'color_id' => $color,
            ]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        $variants = $this->resolveColorVariants($clotheService->getClotheVariantsBySlug($slug), $color);
        $variant = $variants[0] ?? null;
        if (!$variant instanceof ClothesVariant) {
            throw $this->createNotFoundException('Variant not found.');
        }

        $clothe = $this->resolveVariantClothe($variant);

        try {
            $newColor = $this->resolveColor($request, $entityManager);
            $now = new \DateTimeImmutable();
            $price = $request->request->getInt('price');
            if ($price <= 0) {
                throw new \InvalidArgumentException('Renseigne un prix valide.');
            }

            $description = $this->normalizeNullableText($request->request->get('description'));
            $metaDescription = $this->normalizeMetaDescription($request->request->get('metadescription'));
            $newSlug = $this->createVariantSlug((string) $clothe->getName(), $newColor);

            foreach ($variants as $variant) {
                $variantName = $this->createVariantName((string) $clothe->getName(), $newColor, $variant->getSize());
                $variant
                    ->setName($variantName)
                    ->setSlug($this->createVariantSlug((string) $clothe->getName(), $newColor))
                    ->setColor($newColor)
                    ->setSku($this->createSku($clothe, $newColor, $variant->getSize()))
                    ->setDescription($description)
                    ->setMetadescription($metaDescription)
                    ->setEditedAt($now);

                $this->assertVariantUnique($variant, $entityManager);
            }

            $clothe
                ->setPrice($price)
                ->setEditedAt($now);
            $entityManager->flush();
            $flashService->success('Variant mis a jour.');
            $logger->info('Clothe variant group updated.', [
                'slug' => $slug,
                'color_id' => $color,
                'variants_count' => count($variants),
            ]);
        } catch (\InvalidArgumentException $exception) {
            $flashService->error($exception->getMessage());
            $logger->warning('Clothe variant group update rejected.', [
                'slug' => $slug,
                'color_id' => $color,
                'error' => $exception->getMessage(),
            ]);
        }

        return $this->redirectToRoute('app_clothes_show', ['slug' => $newSlug ?? $slug]);
    }

    #[Route('/clothes/variants/{id}/delete', name: 'app_clothes_variant_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        ClothesVariant $variant,
        Request $request,
        EntityManagerInterface $entityManager,
        FlashService $flashService,
        LoggerService $logger,
    ): RedirectResponse {
        $clothe = $this->resolveVariantClothe($variant);
        $slug = (string) $clothe->getSlug();

        if (!$this->isCsrfTokenValid('clothe_variant_delete_'.$variant->getId(), (string) $request->request->get('_csrf_token'))) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for clothe variant deletion.', ['variant_id' => $variant->getId()]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        if ($clothe->getVariants()->count() <= 1) {
            $flashService->error('Un vetement doit conserver au moins une variante.');

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        $variantId = $variant->getId();
        $clothe
            ->removeVariant($variant)
            ->setEditedAt(new \DateTimeImmutable());
        $entityManager->remove($variant);
        $entityManager->flush();
        $flashService->success('Variante supprimee.');
        $logger->info('Clothe variant deleted.', [
            'clothe_id' => $clothe->getId(),
            'variant_id' => $variantId,
        ]);

        return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
    }

    private function renderModalStream(string $html): Response
    {
        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    /**
     * @param list<ClothesVariant> $variants
     */
    private function resolveMainClothe(array $variants): Clothes
    {
        $variant = $variants[0] ?? null;
        $clothe = $variant instanceof ClothesVariant ? $variant->getClothes() : null;

        if (!$clothe instanceof Clothes) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        return $clothe;
    }

    private function resolveVariantClothe(ClothesVariant $variant): Clothes
    {
        $clothe = $variant->getClothes();

        if (!$clothe instanceof Clothes) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        return $clothe;
    }

    /**
     * @param list<ClothesVariant> $variants
     * @return list<ClothesVariant>
     */
    private function resolveColorVariants(array $variants, int $colorId): array
    {
        return array_values(array_filter(
            $variants,
            static fn (ClothesVariant $variant): bool => $variant->getColor()?->getId() === $colorId,
        ));
    }

    private function resolveColor(Request $request, EntityManagerInterface $entityManager): Clothescolor
    {
        $newColorName = trim((string) $request->request->get('newColorName', ''));
        if ($newColorName !== '') {
            $colorHex = ltrim(trim((string) $request->request->get('newColorHex', '')), '#');
            if ($colorHex !== '' && !preg_match('/^[a-fA-F0-9]{6}$/', $colorHex)) {
                throw new \InvalidArgumentException('Le code couleur doit etre au format hexadecimal.');
            }

            $existingColor = $entityManager->getRepository(Clothescolor::class)->findOneBy(['name' => $newColorName]);
            if ($existingColor instanceof Clothescolor) {
                return $existingColor;
            }

            $now = new \DateTimeImmutable();
            $color = (new Clothescolor())
                ->setName($newColorName)
                ->setHexa($colorHex !== '' ? strtolower($colorHex) : null)
                ->setCreatedAt($now)
                ->setEditedAt($now);

            $entityManager->persist($color);

            return $color;
        }

        if ((string) $request->request->get('color', '') === '__new__') {
            throw new \InvalidArgumentException('Renseigne le nom de la nouvelle couleur.');
        }

        $color = $entityManager->getRepository(Clothescolor::class)->find($request->request->getInt('color'));
        if (!$color instanceof Clothescolor) {
            throw new \InvalidArgumentException('Selectionne une couleur valide.');
        }

        return $color;
    }

    private function resolveSize(string $sizeName, EntityManagerInterface $entityManager): Clothessize
    {
        $sizeName = trim($sizeName);
        if (!in_array($sizeName, ClotheService::AVAILABLE_SIZES, true)) {
            throw new \InvalidArgumentException('Selectionne une taille valide.');
        }

        $size = $entityManager->getRepository(Clothessize::class)->findOneBy(['name' => $sizeName]);
        if ($size instanceof Clothessize) {
            return $size;
        }

        $now = new \DateTimeImmutable();
        $size = (new Clothessize())
            ->setName($sizeName)
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $entityManager->persist($size);

        return $size;
    }

    /**
     * @return list<Clothessize>
     */
    private function resolveSizes(Request $request, EntityManagerInterface $entityManager): array
    {
        $submittedSizes = $request->request->all('sizes');
        if ($submittedSizes === []) {
            $legacySize = trim((string) $request->request->get('size', ''));
            $submittedSizes = $legacySize !== '' ? [$legacySize] : [];
        }

        if (!is_array($submittedSizes)) {
            $submittedSizes = [];
        }

        $sizeNames = array_values(array_unique(array_filter(array_map(
            static fn (mixed $size): string => trim((string) $size),
            $submittedSizes,
        ))));

        if ($sizeNames === []) {
            throw new \InvalidArgumentException('Selectionne au moins une taille.');
        }

        return array_map(
            fn (string $sizeName): Clothessize => $this->resolveSize($sizeName, $entityManager),
            $sizeNames,
        );
    }

    private function resolveStock(Request $request): int
    {
        $stock = filter_var($request->request->get('stock'), FILTER_VALIDATE_INT);
        if ($stock === false || $stock < 0) {
            throw new \InvalidArgumentException('Le stock doit etre un entier positif ou nul.');
        }

        return $stock;
    }

    private function normalizeNullableText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function normalizeMetaDescription(mixed $value): ?string
    {
        $value = $this->normalizeNullableText($value);
        if ($value !== null && mb_strlen($value) > 200) {
            throw new \InvalidArgumentException('La meta description est limitee a 200 caracteres.');
        }

        return $value;
    }

    private function createSku(Clothes $clothe, Clothescolor $color, ?Clothessize $size): string
    {
        $slugger = new AsciiSlugger();

        return strtoupper(sprintf(
            '%s-%s-%s',
            (string) $slugger->slug((string) $clothe->getName()),
            (string) $slugger->slug((string) $color->getName()),
            (string) $slugger->slug((string) $size?->getName()),
        ));
    }

    private function createVariantName(string $name, Clothescolor $color, ?Clothessize $size): string
    {
        return trim(sprintf('%s %s %s', $name, (string) $color->getName(), (string) $size?->getName()));
    }

    private function createVariantSlug(string $name, Clothescolor $color): string
    {
        return strtolower((string) (new AsciiSlugger())->slug(trim(sprintf('%s %s', $name, (string) $color->getName()))));
    }

    private function assertVariantUnique(ClothesVariant $variant, EntityManagerInterface $entityManager): void
    {
        $clothe = $this->resolveVariantClothe($variant);
        $variantId = $variant->getId();
        $colorName = (string) $variant->getColor()?->getName();
        $sizeName = (string) $variant->getSize()?->getName();

        foreach ($clothe->getVariants() as $existingVariant) {
            if ($existingVariant === $variant) {
                continue;
            }

            if (
                mb_strtolower((string) $existingVariant->getColor()?->getName()) === mb_strtolower($colorName)
                && mb_strtolower((string) $existingVariant->getSize()?->getName()) === mb_strtolower($sizeName)
            ) {
                throw new \InvalidArgumentException(sprintf(
                    'Une variante existe deja pour la couleur %s et la taille %s.',
                    $colorName,
                    $sizeName,
                ));
            }
        }

        $existingSku = $entityManager->getRepository(ClothesVariant::class)->findOneBy(['sku' => $variant->getSku()]);
        if ($existingSku instanceof ClothesVariant && $existingSku->getId() !== $variantId) {
            throw new \InvalidArgumentException(sprintf('Le SKU %s est deja utilise.', (string) $variant->getSku()));
        }

    }
}
