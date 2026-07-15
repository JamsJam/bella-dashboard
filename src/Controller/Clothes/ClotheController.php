<?php

namespace App\Controller\Clothes;

use App\Application\Clothes\DTO\ClotheImageInput;
use App\Application\Clothes\Guard\ClotheNameGuard;
use App\Application\Clothes\Guard\ClotheOnlineGuard;
use App\Application\Clothes\Services\ClothePublicationService;
use App\Application\Clothes\Services\ClotheRenameService;
use App\Application\Clothes\Services\ClotheService;
use App\Application\Clothes\Services\ClotheSizeGuideService;
use App\Entity\Category\Category;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use App\Entity\Collections\Collections;
use App\Notifier\Services\FlashService;
use App\Repository\Category\CategoryRepository;
use App\Repository\Clothes\ClothesRepository;
use App\Repository\Collections\CollectionsRepository;
use App\Service\BreadscrumbsService;
use App\Service\LoggerService;
use App\UI\ProductGrid\ProductGridFilterModel;
use App\UI\ProductGrid\ProductGridItemModel;
use App\UI\ProductGrid\ProductGridViewModel;
use App\UI\Toggle\ToggleActionModel;
use App\UI\Toggle\ToggleModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class ClotheController extends AbstractController
{
    private const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg'];
    private const IMAGE_MIME_TYPES = ['image/png', 'image/jpeg'];

    #[Route('/clothes', name: 'app_clothes', methods: ['GET'])]
    public function index(
        Request $request,
        BreadscrumbsService $breadscrumbs,
        ClotheService $clotheService,
        CategoryRepository $categoryRepository,
        CollectionsRepository $collectionsRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response
    {
        $category = $request->query->getInt('category') ?: null;
        $collection = $request->query->getInt('collection') ?: null;
        $bestsellerOnly = $request->query->getBoolean('bestseller');
        $online = $this->resolveOnlineFilter($request->query->get('online'));
        $variantGroups = $clotheService->getDistinctClotheByName(
            sortBy: 'name',
            direction: 'asc',
            query: (string) $request->query->get('search', ''),
            category: $category,
            collection: $collection,
            bestsellerOnly: $bestsellerOnly,
            online: $online,
        );

        return $this->render('clothes/index.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve((string) $request->attributes->get('_route')),
            'tabs' => $this->createTabs($bestsellerOnly),
            'gridData' => new ProductGridViewModel(
                id: 'clothes-grid',
                items: array_map(fn (ClothesVariant $variant): ProductGridItemModel => $this->mapVariantGroup($variant), $variantGroups),
                filters: $this->createFilters($categoryRepository, $collectionsRepository, $category, $collection, $online),
                searchRoute: 'app_search_clothes',
                title: 'Bibliotheque de vetements',
                searchPlaceholder: 'Rechercher un vetement...',
                noResultsLabel: 'Aucun vetement trouve',
                detailActionLabel: 'Voir le detail',
                detailUrlTemplate: '/clothes/__SLUG__',
                deleteUrlTemplate: '#',
                deleteCsrfTokenId: null,
                resourceKey: 'clothes',
                resourceParamName: 'resource',
                extraSearchParams: $bestsellerOnly ? ['bestseller' => '1'] : [],
                showSelectionActions: true,
                selectionCountLabel: 'vetement(s) selectionne(s)',
                selectAllLabel: 'Tout selectionner',
                clearSelectionLabel: 'Annuler la selection',
                selectionActions: [
                    [
                        'id' => 'bestseller',
                        'label' => 'Bestseller',
                        'href' => '#',
                        'class' => 'product-grid__action--bestseller',
                        'attr' => [
                            'data-action' => 'click->product-grid-search#onBestsellerSelection',
                            'data-bestseller-url' => $this->generateUrl('app_clothes_bestsellers_update'),
                            'data-bestseller-csrf-token' => $csrfTokenManager->getToken('clothe_bestseller')->getValue(),
                        ],
                    ],
                    [
                        'id' => 'carousel',
                        'label' => 'Mise en avant',
                        'href' => '#',
                        'class' => 'product-grid__action--carousel',
                        'attr' => [
                            'data-action' => 'click->product-grid-search#onFeaturedSelection',
                            'data-featured-url' => $this->generateUrl('app_clothes_featured_update'),
                            'data-featured-csrf-token' => $csrfTokenManager->getToken('clothe_featured')->getValue(),
                        ],
                    ],
                ],
                selectedFilters: [
                    'category' => $category,
                    'collection' => $collection,
                    'bestseller' => $bestsellerOnly,
                    'online' => $online,
                ],
                part: 'clothes',
            ),
        ]);
    }

    #[Route('/clothes/search', name: 'app_search_clothes', methods: ['GET'])]
    public function search(
        #[MapQueryParameter] ?string $search,
        #[MapQueryParameter] ?int $category,
        #[MapQueryParameter] ?int $collection,
        #[MapQueryParameter] ?bool $bestseller,
        #[MapQueryParameter] ?string $online,
        ClotheService $clotheService,
    ): JsonResponse {
        $variantGroups = $clotheService->getDistinctClotheByName(
            sortBy: 'name',
            direction: 'asc',
            query: $search ?? '',
            category: $category,
            collection: $collection,
            bestsellerOnly: $bestseller ?? false,
            online: $this->resolveOnlineFilter($online),
        );

        return $this->json([
            'items' => array_map(fn (ClothesVariant $variant): array => $this->mapVariantGroup($variant)->toArray(), $variantGroups),
        ]);
    }

    private function createTabs(bool $bestsellerOnly): array
    {
        return [
            ['id' => 'add', 'label' => 'Ajouter', 'href' => $this->generateUrl('app_clothe_add'), 'isActive' => false],
            [
                'id' => 'bestseller',
                'label' => 'Bestseller',
                'href' => $this->generateUrl('app_clothes_bestsellers_modal'),
                'isActive' => $bestsellerOnly,
                'attr' => [
                    'data-turbo-stream' => 'true',
                ],
            ],
            [
                'id' => 'featured',
                'label' => 'Mises en avant',
                'href' => $this->generateUrl('app_clothes_featured_modal'),
                'isActive' => false,
                'attr' => [
                    'data-turbo-stream' => 'true',
                ],
            ],
            ['id' => 'back', 'label' => 'Retour', 'href' => $this->generateUrl('app_dashboard'), 'isActive' => false],
        ];
    }

    /**
     * @return list<ProductGridFilterModel>
     */
    private function createFilters(
        CategoryRepository $categoryRepository,
        CollectionsRepository $collectionsRepository,
        ?int $selectedCategory,
        ?int $selectedCollection,
        ?bool $selectedOnline,
    ): array {
        return [
            new ProductGridFilterModel(
                id: 'category',
                label: 'Categorie',
                options: $this->createOptions($categoryRepository->findBy([], ['name' => 'ASC']), 'Toutes les categories'),
                selected: $selectedCategory !== null ? (string) $selectedCategory : null,
            ),
            new ProductGridFilterModel(
                id: 'collection',
                label: 'Collection',
                options: $this->createOptions($collectionsRepository->findBy([], ['name' => 'ASC']), 'Toutes les collections'),
                selected: $selectedCollection !== null ? (string) $selectedCollection : null,
            ),
            new ProductGridFilterModel(
                id: 'online',
                label: 'Mise en ligne',
                options: [
                    ['value' => '', 'label' => 'Indifferent'],
                    ['value' => '1', 'label' => 'En ligne'],
                    ['value' => '0', 'label' => 'Hors ligne'],
                ],
                selected: $selectedOnline === null ? null : ($selectedOnline ? '1' : '0'),
            ),
        ];
    }

    /**
     * @param list<Category|Collections> $entities
     * @return list<array{value: string, label: string}>
     */
    private function createOptions(array $entities, string $emptyLabel): array
    {
        $options = [
            ['value' => '', 'label' => $emptyLabel],
        ];

        foreach ($entities as $entity) {
            $options[] = [
                'value' => (string) $entity->getId(),
                'label' => (string) $entity->getName(),
            ];
        }

        return $options;
    }

    private function mapClothe(Clothes $clothe): ProductGridItemModel
    {
        $images = $clothe->getImages() ?? [];

        return new ProductGridItemModel(
            id: (string) $clothe->getId(),
            name: (string) $clothe->getName(),
            imageUrl: (string) ($images[0] ?? $clothe->getCollection()?->getImage() ?? ''),
            slug: (string) $clothe->getSlug(),
            isOnline: $this->isEffectivelyOnline($clothe),
            attributes: [
                'collection' => $clothe->getCollection()?->getName(),
                'category' => $clothe->getCollection()?->getCategory()?->getName(),
                'variants' => (string) $clothe->getVariants()->count(),
                'stock' => (string) $clothe->getTotalStock(),
            ],
        );
    }

    private function mapVariantGroup(ClothesVariant $variant): ProductGridItemModel
    {
        $clothe = $variant->getClothes();
        $images = $variant->getImages() ?? [];
        $groupVariants = $this->filterVariantsByColor($clothe?->getVariants()->toArray() ?? [], $variant->getColor()?->getId());
        $stock = 0;

        foreach ($groupVariants as $groupVariant) {
            $stock += $groupVariant->getStock();
        }

        return new ProductGridItemModel(
            id: (string) $variant->getId(),
            name: trim(sprintf('%s %s', (string) $clothe?->getName(), (string) $variant->getColor()?->getName())),
            imageUrl: (string) ($images[0] ?? $clothe?->getCollection()?->getImage() ?? ''),
            slug: (string) $variant->getSlug(),
            isOnline: $this->isVariantGroupEffectivelyOnline($variant),
            attributes: [
                'collection' => $clothe?->getCollection()?->getName(),
                'category' => $clothe?->getCollection()?->getCategory()?->getName(),
                'sizes' => (string) count($groupVariants),
                'stock' => (string) $stock,
            ],
        );
    }

    private function isVariantGroupEffectivelyOnline(ClothesVariant $variant): bool
    {
        $clothe = $variant->getClothes();
        $collection = $clothe?->getCollection();
        $category = $collection?->getCategory();

        return (bool) $category?->isOnline()
            && (bool) $collection?->isOnline()
            && (bool) $clothe?->isOnline()
            && $variant->isOnline()
            && $variant->getStock() > 0;
    }

    private function isEffectivelyOnline(Clothes $clothe): bool
    {
        $collection = $clothe->getCollection();
        $category = $collection?->getCategory();

        return (bool) $category?->isOnline()
            && (bool) $collection?->isOnline()
            && (bool) $clothe->isOnline();
    }

    private function resolveOnlineFilter(mixed $value): ?bool
    {
        return match ((string) $value) {
            '1', 'online', 'true' => true,
            '0', 'offline', 'false' => false,
            default => null,
        };
    }

    #[Route('/clothes/featured', name: 'app_clothes_featured_update', methods: ['POST'])]
    public function updateFeatured(
        Request $request,
        ClothesRepository $clothesRepository,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        FlashService $flashService,
        LoggerService $logger,
    ): Response {
        $token = new CsrfToken('clothe_featured', $this->readCsrfToken($request));
        if (!$csrfTokenManager->isTokenValid($token)) {
            $logger->warning('Invalid CSRF token for featured clothes update.');

            return $this->json(['success' => false, 'error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $mode = $this->readBestsellerMode($request);
        $slugs = $mode === 'remove'
            ? $this->readBestsellerSlugs($request)
            : $this->extractVariantSlugs($this->findVariantsByIds($this->readBestsellerIds($request), $entityManager));

        if ($mode === 'replace') {
            $keptSlugMap = array_flip($slugs);

            foreach ($clothesRepository->findFeaturedVariants() as $variant) {
                if ($variant instanceof ClothesVariant && !isset($keptSlugMap[(string) $variant->getSlug()])) {
                    $variant->setIsInCarousel(false);
                }
            }
        }

        foreach ($clothesRepository->findVariantsBySlugs($slugs) as $variant) {
            if ($variant instanceof ClothesVariant) {
                $variant->setIsInCarousel($mode !== 'remove');
            }
        }

        $entityManager->flush();
        $flashService->success('Mise en avant des vetements mise a jour.');
        $logger->info('Featured clothes updated.', [
            'mode' => $mode,
            'slugs_count' => count($slugs),
        ]);

        if ($this->wantsTurboStream($request) && !$request->isXmlHttpRequest()) {
            return new Response(
                '<turbo-stream action="update" target="modal-root"><template></template></turbo-stream>',
                Response::HTTP_OK,
                ['Content-Type' => 'text/vnd.turbo-stream.html'],
            );
        }

        if (!$this->wantsJson($request)) {
            return $this->redirectToRoute('app_clothes');
        }

        return $this->json([
            'success' => true,
            'checked' => $mode !== 'remove',
        ]);
    }

    #[Route('/clothes/featured/modal', name: 'app_clothes_featured_modal', methods: ['GET'])]
    public function featuredModal(
        ClothesRepository $clothesRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $html = $this->renderView('clothes/_featured_modal.html.twig', [
            'action' => $this->generateUrl('app_clothes_featured_update'),
            'csrfToken' => $csrfTokenManager->getToken('clothe_featured')->getValue(),
            'featuredClothes' => $clothesRepository->findDistinctFeaturedEntities(),
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    #[Route('/clothes/{slug}', name: 'app_clothes_show', methods: ['GET'])]
    public function show(
        string $slug,
        Request $request,
        BreadscrumbsService $breadscrumbs,
        ClotheService $clotheService,
        ClotheSizeGuideService $clotheSizeGuideService,
        ClotheOnlineGuard $clotheOnlineGuard,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $variants = $clotheService->getClotheVariantsBySlug($slug);

        if ($variants === []) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        $mainClothe = $this->resolveMainClothe($variants);
        $publicationValidation = $clotheOnlineGuard->canPublish($mainClothe);

        return $this->render('clothes/show.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve(
                route: (string) $request->attributes->get('_route'),
                routeParams: ['slug' => $slug],
                currentLabel: (string) $mainClothe->getName(),
            ),
            'tabs' => $this->createShowTabs($slug, (int) $mainClothe->getId()),
            'clothe' => $this->mapClotheDetails($mainClothe, $variants, $clotheOnlineGuard, $clotheSizeGuideService, $csrfTokenManager),
            'sameCollectionClothes' => array_map(
                fn (Clothes $clothe): ProductGridItemModel => $this->mapClothe($clothe),
                $clotheService->getSameCollectionClothes($slug),
            ),
            'highlightImageModalUrls' => [
                'bestseller' => $this->generateUrl('app_clothes_highlight_image_modal', ['slug' => $slug, 'slot' => 'bestseller']),
                'featured' => $this->generateUrl('app_clothes_highlight_image_modal', ['slug' => $slug, 'slot' => 'carousel']),
            ],
            'sizesManageUrl' => $this->generateUrl('app_clothes_sizes_modal', ['slug' => $slug]),
            'bestsellerToggle' => $this->renderClotheBestsellerToggle($mainClothe, $csrfTokenManager),
            'featuredToggle' => $this->renderClotheFeaturedToggle($mainClothe, $csrfTokenManager),
            'sizeGuideUpdateUrl' => $this->generateUrl('app_clothes_size_guide_update', ['slug' => $slug]),
            'sizeGuidePreviewUrl' => $this->generateUrl('app_clothes_size_guide_preview', ['slug' => $slug]),
            'sizeGuideCsrfToken' => $csrfTokenManager->getToken('clothe_size_guide_'.$slug)->getValue(),
            'canPublish' => $publicationValidation->canPublish(),
            'publicationErrors' => $publicationValidation->getErrors(),
        ]);
    }

    #[Route('/clothes/{slug}/edit/modal', name: 'app_clothes_edit_modal', methods: ['GET'])]
    public function editModal(
        string $slug,
        ClotheService $clotheService,
        EntityManagerInterface $entityManager,
    ): Response {
        $variants = $clotheService->getClotheVariantsBySlug($slug);
        if ($variants === []) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        $mainClothe = $this->resolveMainClothe($variants);

        return $this->render('clothes/_edit_modal.html.twig', [
            'clothe' => $mainClothe,
            'collections' => $entityManager->getRepository(Collections::class)->findBy([], ['name' => 'ASC']),
            'action' => $this->generateUrl('app_clothes_update', ['slug' => $slug]),
            "slug" => $slug,
        ]);
    }

    #[Route('/clothes/{slug}/edit', name: 'app_clothes_update', methods: ['POST'])]
    public function update(
        string $slug,
        Request $request,
        ClotheService $clotheService,
        ClotheRenameService $clotheRenameService,
        EntityManagerInterface $entityManager,
        FlashService $flashService,
        LoggerService $logger,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('clothe_edit_'.$slug, (string) $request->getPayload()->get('_csrf_token'))) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for clothe edit.', ['slug' => $slug]);
            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        $variants = $clotheService->getClotheVariantsBySlug($slug);
        if ($variants === []) {
            $logger->warning('Clothe not found for update.', ['slug' => $slug]);
            throw $this->createNotFoundException('Clothe not found.');
        }


        $collection = $entityManager->getRepository(Collections::class)->find($request->request->getInt('collection'));
        $price = $request->request->getInt('price');
        $name = (string) $request->request->get('name', '');

        if (
            !$collection instanceof Collections
            || $price <= 0
        ) {
            $flashService->error('Categorie, collection ou prix invalide.');
            $logger->warning('Invalid data for clothe update.', ['slug' => $slug]);
            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        try {
            $mainClothe = $this->resolveMainClothe($variants);
            $newSlug = $clotheRenameService->renameClothe($mainClothe, $slug, $name);
            $now = new \DateTimeImmutable();
            $mainClothe
                ->setCollection($collection)
                ->setPrice($price)
                ->setEditedAt($now);
            foreach ($variants as $variant) {
                if ($variant instanceof ClothesVariant) {
                    $variant
                        ->setSku($this->createVariantSku($mainClothe, $variant->getColor(), $variant->getSize()))
                        ->setEditedAt($now);
                }
            }
        } catch (\InvalidArgumentException $exception) {
            $flashService->error($exception->getMessage());
            $logger->warning('Clothe rename rejected.', [
                'slug' => $slug,
                'error' => $exception->getMessage(),
            ]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        $entityManager->flush();
        $flashService->success('Informations du vetement mises a jour.');
        $logger->info('Clothe updated.', [
            'old_slug' => $slug,
            'new_slug' => $newSlug,
            'collection_id' => $collection->getId(),
        ]);

        return $this->redirectToRoute('app_clothes_show', ['slug' => $newSlug]);
    }

    #[Route('/clothes/{id}', name: 'app_clothes_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(
        Clothes $clothe,
        Request $request,
        EntityManagerInterface $entityManager,
        LoggerService $logger,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('clothe_delete', (string) $request->headers->get('X-CSRF-TOKEN', ''))) {
            $logger->warning('Invalid CSRF token for clothe deletion.', [
                'clothe_id' => $clothe->getId(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Invalid CSRF token.',
            ], Response::HTTP_FORBIDDEN);
        }

        $id = $clothe->getId();
        $this->removeClothe($clothe, $entityManager, $logger);

        return $this->json([
            'success' => true,
            'id' => $id,
        ]);
    }

    #[Route('/clothes/{id}/delete/modal', name: 'app_clothes_delete_modal', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function deleteModal(
        Clothes $clothe,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $html = $this->renderView('clothes/_delete_modal.html.twig', [
            'clothe' => $clothe,
            'action' => $this->generateUrl('app_clothes_delete_confirm', ['id' => $clothe->getId()]),
            'csrfToken' => $csrfTokenManager->getToken('clothe_delete_'.$clothe->getId())->getValue(),
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    #[Route('/clothes/{id}/delete', name: 'app_clothes_delete_confirm', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function confirmDelete(
        Clothes $clothe,
        Request $request,
        EntityManagerInterface $entityManager,
        FlashService $flashService,
        LoggerService $logger,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('clothe_delete_'.$clothe->getId(), (string) $request->request->get('_csrf_token'))) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for clothe deletion.', [
                'clothe_id' => $clothe->getId(),
            ]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $clothe->getSlug()]);
        }

        $this->removeClothe($clothe, $entityManager, $logger);
        $flashService->success('Vetement supprime.');

        return $this->redirectToRoute('app_clothes');
    }

    private function removeClothe(Clothes $clothe, EntityManagerInterface $entityManager, LoggerService $logger): void
    {
        $id = $clothe->getId();
        $slug = $clothe->getSlug();

        $entityManager->remove($clothe);
        $entityManager->flush();
        $logger->info('Clothe deleted.', [
            'clothe_id' => $id,
            'slug' => $slug,
        ]);
    }

    #[Route('/clothes/{slug}/stock/modal', name: 'app_clothes_stock_modal', methods: ['GET'])]
    public function stockModal(
        string $slug,
        ClotheService $clotheService,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $variants = $clotheService->getClotheVariantsBySlug($slug);
        if ($variants === []) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        return $this->render('clothes/_stock_modal.html.twig', [
            'variants' => $variants,
            'colors' => $entityManager->getRepository(Clothescolor::class)->findBy([], ['name' => 'ASC']),
            'availableSizes' => ClotheService::AVAILABLE_SIZES,
            'action' => $this->generateUrl('app_clothes_stock_update', ['slug' => $slug]),
            'csrfToken' => $csrfTokenManager->getToken('clothe_stock_'.$slug)->getValue(),
        ]);
    }

    #[Route('/clothes/{slug}/stock', name: 'app_clothes_stock_update', methods: ['POST'])]
    public function updateStock(
        string $slug,
        Request $request,
        ClotheService $clotheService,
        EntityManagerInterface $entityManager,
        FlashService $flashService,
        LoggerService $logger,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('clothe_stock_'.$slug, (string) $request->request->get('_csrf_token'))) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for clothe stock update.', [
                'slug' => $slug,
            ]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        $variants = $clotheService->getClotheVariantsBySlug($slug);
        if ($variants === []) {
            $logger->warning('Clothe not found for stock update.', [
                'slug' => $slug,
            ]);

            throw $this->createNotFoundException('Clothe not found.');
        }

        $submittedStocks = $request->request->all('stocks');
        $submittedColors = $request->request->all('colors');
        $submittedSizes = $request->request->all('sizes');
        $submittedOnline = $request->request->all('online');
        $stocks = [];
        $colors = [];
        $sizes = [];
        $onlineStates = [];
        $mainClothe = $this->resolveMainClothe($variants);

        foreach ($variants as $variant) {
            if (!$variant instanceof ClothesVariant || $variant->getId() === null) {
                continue;
            }

            $variantId = (string) $variant->getId();
            $stock = filter_var($submittedStocks[(string) $variant->getId()] ?? null, FILTER_VALIDATE_INT);
            if ($stock === false || $stock < 0) {
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
            $onlineStates[$variant->getId()] = isset($submittedOnline[$variantId]) && $stock > 0;
        }

        $now = new \DateTimeImmutable();
        foreach ($variants as $variant) {
            if (!$variant instanceof ClothesVariant || $variant->getId() === null) {
                continue;
            }

            $stock = $stocks[$variant->getId()];
            $variant
                ->setColor($colors[$variant->getId()])
                ->setSize($sizes[$variant->getId()])
                ->setSku($this->createVariantSku($mainClothe, $colors[$variant->getId()], $sizes[$variant->getId()]))
                ->setStock($stock)
                ->setIsOnline($onlineStates[$variant->getId()])
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

    #[Route('/clothes/{id}/online/{state}', name: 'app_clothes_toggle_online', requirements: ['id' => '\d+', 'state' => 'on|off'], methods: ['POST'])]
    public function toggleOnline(
        Clothes $clothe,
        string $state,
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
        ClothePublicationService $clothePublicationService,
        LoggerService $logger,
    ): JsonResponse {
        $token = new CsrfToken($this->getOnlineCsrfTokenId($clothe), (string) $request->headers->get('X-CSRF-TOKEN', ''));

        if (!$csrfTokenManager->isTokenValid($token)) {
            $logger->warning('Invalid CSRF token for clothe online toggle.', [
                'clothe_id' => $clothe->getId(),
                'state' => $state,
            ]);

            return $this->json(['success' => false, 'error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        if ($state === 'on' && !$clothePublicationService->publish($clothe)) {
            $logger->warning('Clothe publication rejected.', [
                'clothe_id' => $clothe->getId(),
                'slug' => $clothe->getSlug(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Clothe cannot be published.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($state === 'off') {
            $clothePublicationService->unpublish($clothe);
        }

        return $this->json([
            'success' => true,
            'isOnline' => $clothe->isOnline(),
        ]);
    }

    #[Route('/clothes/variant/{id}/online/{state}', name: 'app_clothes_variant_toggle_online', requirements: ['id' => '\d+', 'state' => 'on|off'], methods: ['POST'])]
    public function toggleVariantOnline(
        ClothesVariant $variant,
        string $state,
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
        EntityManagerInterface $entityManager,
        LoggerService $logger,
    ): JsonResponse {
        $token = new CsrfToken($this->getVariantOnlineCsrfTokenId($variant), (string) $request->headers->get('X-CSRF-TOKEN', ''));

        if (!$csrfTokenManager->isTokenValid($token)) {
            $logger->warning('Invalid CSRF token for clothe variant online toggle.', [
                'variant_id' => $variant->getId(),
                'state' => $state,
            ]);

            return $this->json(['success' => false, 'error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        if ($state === 'on' && $variant->getStock() <= 0) {
            return $this->json([
                'success' => false,
                'error' => 'Variant cannot be published without stock.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $variant
            ->setIsOnline($state === 'on')
            ->setEditedAt(new \DateTimeImmutable());
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'isOnline' => $variant->isOnline(),
        ]);
    }

    #[Route('/clothes/{slug}/size-guide/preview', name: 'app_clothes_size_guide_preview', methods: ['POST'])]
    public function previewSizeGuide(
        string $slug,
        Request $request,
        ClotheService $clotheService,
        ClotheSizeGuideService $clotheSizeGuideService,
    ): Response {
        $variants = $clotheService->getClotheVariantsBySlug($slug);
        if ($variants === []) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        $mainClothe = $this->resolveMainClothe($variants);
        $measurements = $request->request->all('measurements');
        $measurementTypes = $request->request->all('measurement_types');
        $sizeGuide = $clotheSizeGuideService->buildPreviewView(
            mainClothe: $mainClothe,
            variants: $variants,
            selectedTypeCodes: is_array($measurementTypes) ? $measurementTypes : [],
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

    #[Route('/clothes/{slug}/size-guide', name: 'app_clothes_size_guide_update', methods: ['POST'])]
    public function updateSizeGuide(
        string $slug,
        Request $request,
        ClotheService $clotheService,
        ClotheSizeGuideService $clotheSizeGuideService,
        FlashService $flashService,
        LoggerService $logger,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('clothe_size_guide_'.$slug, (string) $request->request->get('_csrf_token'))) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for clothe size guide update.', [
                'slug' => $slug,
            ]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        $variants = $clotheService->getClotheVariantsBySlug($slug);
        if ($variants === []) {
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
            selectedTypeCodes: is_array($measurementTypes) ? $measurementTypes : [],
        );

        $flashService->success('Guide des tailles mis a jour.');
        $logger->info('Clothe size guide updated.', [
            'slug' => $slug,
        ]);

        return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
    }

    #[Route('/clothes/{slug}/highlight-image/{slot}/modal', name: 'app_clothes_highlight_image_modal', requirements: ['slot' => 'carousel|bestseller'], methods: ['GET'])]
    public function highlightImageModal(
        string $slug,
        string $slot,
        ClotheService $clotheService,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response
    {
        $variants = $clotheService->getClotheVariantsBySlug($slug);

        if ($variants === []) {
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
            $selectedImage ??= $slot === 'carousel'
                ? $variant->getHighlightImage()
                : $variant->getBestsellerImage();
        }

        $html = $this->renderView('clothes/_highlight_image_modal.html.twig', [
            'slot' => $slot,
            'slotLabel' => $slot === 'carousel' ? 'carrousel' : 'bestseller',
            'clotheName' => $mainClothe->getName(),
            'images' => array_values(array_unique(array_filter($images))),
            'selectedImage' => $selectedImage,
            'action' => $this->generateUrl('app_clothes_highlight_image_update', ['slug' => $slug, 'slot' => $slot]),
            'csrfToken' => $csrfTokenManager->getToken('clothe_highlight_image_'.$slug.'_'.$slot)->getValue(),
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    #[Route('/clothes/{slug}/images/modal', name: 'app_clothes_images_modal', methods: ['GET'])]
    public function imagesModal(
        string $slug,
        Request $request,
        ClotheService $clotheService,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $variants = $clotheService->getClotheVariantsBySlug($slug);

        if ($variants === []) {
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
            'clotheName' => $colorId !== null && $imageVariants !== []
                ? sprintf('%s - %s', (string) $mainClothe->getName(), (string) $imageVariants[0]->getColor()?->getName())
                : $mainClothe->getName(),
            'images' => array_values(array_unique(array_filter($images))),
            'action' => $this->generateUrl('app_clothes_images_update', array_filter([
                'slug' => $slug,
                'color' => $colorId,
            ], static fn (mixed $value): bool => $value !== null)),
            'csrfToken' => $csrfTokenManager->getToken('clothe_images_'.$slug)->getValue(),
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    #[Route('/clothes/{slug}/images', name: 'app_clothes_images_update', methods: ['POST'])]
    public function updateImages(
        string $slug,
        Request $request,
        ClotheService $clotheService,
        EntityManagerInterface $entityManager,
        FlashService $flashService,
        LoggerService $logger,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('clothe_images_'.$slug, (string) $request->request->get('_csrf_token'))) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for clothe images update.', [
                'slug' => $slug,
            ]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        $variants = $clotheService->getClotheVariantsBySlug($slug);
        if ($variants === []) {
            $logger->warning('Clothe not found for images update.', [
                'slug' => $slug,
            ]);

            throw $this->createNotFoundException('Clothe not found.');
        }

        $mainClothe = $this->resolveMainClothe($variants);
        $colorId = $request->query->getInt('color') ?: null;
        $imageVariants = $this->filterVariantsByColor($variants, $colorId);
        $availableImages = [];

        foreach ($imageVariants as $variant) {
            if ($variant instanceof ClothesVariant) {
                $availableImages = array_merge($availableImages, $variant->getImages() ?? []);
            }
        }

        $availableImages = array_values(array_unique(array_filter($availableImages)));
        $keptImages = array_values(array_unique(array_filter(
            $request->request->all('images'),
            static fn (mixed $image): bool => is_string($image) && in_array($image, $availableImages, true),
        )));

        $uploadedImages = $request->files->all('uploaded_images');
        $storedImages = $this->storeClotheImages(
            is_array($uploadedImages) ? $uploadedImages : [],
            (string) $mainClothe->getName(),
        );

        $images = [
            ...$keptImages,
            ...array_map(static fn (ClotheImageInput $image): string => $image->path, $storedImages),
        ];

        if ($images === []) {
            $flashService->error('Conserve ou ajoute au moins une image.');
            $logger->warning('Clothe images update rejected without image.', [
                'slug' => $slug,
            ]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        $now = new \DateTimeImmutable();
        $mainClothe->setEditedAt($now);

        foreach ($imageVariants as $variant) {
            if ($variant instanceof ClothesVariant) {
                $variant
                    ->setImages($images)
                    ->setEditedAt($now);
            }
        }

        $entityManager->flush();
        $flashService->success($colorId === null ? 'Images du vetement mises a jour.' : 'Images du variant mises a jour.');
        $logger->info('Clothe images updated.', [
            'slug' => $slug,
            'color_id' => $colorId,
            'images_count' => count($images),
        ]);

        return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
    }

    #[Route('/clothes/{slug}/highlight-image/{slot}', name: 'app_clothes_highlight_image_update', requirements: ['slot' => 'carousel|bestseller'], methods: ['POST'])]
    public function updateHighlightImage(
        string $slug,
        string $slot,
        Request $request,
        ClotheService $clotheService,
        EntityManagerInterface $entityManager,
        FlashService $flashService,
        LoggerService $logger,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('clothe_highlight_image_'.$slug.'_'.$slot, (string) $request->request->get('_csrf_token'))) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for clothe highlight image update.', [
                'slug' => $slug,
                'slot' => $slot,
            ]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        $variants = $clotheService->getClotheVariantsBySlug($slug);
        if ($variants === []) {
            $logger->warning('Clothe not found for highlight image update.', [
                'slug' => $slug,
                'slot' => $slot,
            ]);

            throw $this->createNotFoundException('Clothe not found.');
        }

        $mainClothe = $this->resolveMainClothe($variants);
        $selectedImage = trim((string) $request->request->get('selected_image', ''));
        $uploadedImage = $request->files->get('uploaded_image');

        if ($uploadedImage instanceof UploadedFile) {
            $storedImages = $this->storeClotheImages([$uploadedImage], (string) $mainClothe->getName());
            $selectedImage = $storedImages[0]->path ?? '';
        }

        $availableImages = [];
        foreach ($variants as $variant) {
            if ($variant instanceof ClothesVariant) {
                $availableImages = array_merge($availableImages, $variant->getImages() ?? []);
            }
        }

        if ($selectedImage === '' || (!in_array($selectedImage, $availableImages, true) && !($uploadedImage instanceof UploadedFile))) {
            $flashService->error('Selectionne une image valide.');
            $logger->warning('Invalid highlight image selected.', [
                'slug' => $slug,
                'slot' => $slot,
            ]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        $now = new \DateTimeImmutable();
        $mainClothe->setEditedAt($now);

        foreach ($variants as $variant) {
            if (!$variant instanceof ClothesVariant) {
                continue;
            }

            if ($slot === 'carousel') {
                $variant
                    ->setHighlightImage($selectedImage)
                    ->setIsInCarousel(true);
            } else {
                $variant->setBestsellerImage($selectedImage);
            }

            $variant->setEditedAt($now);
        }

        $entityManager->flush();
        $flashService->success($slot === 'carousel' ? 'Image de mise en avant mise a jour.' : 'Image bestseller mise a jour.');
        $logger->info('Clothe highlight image updated.', [
            'slug' => $slug,
            'slot' => $slot,
        ]);

        return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
    }

    #[Route('/clothes/{slug}/sizes/modal', name: 'app_clothes_sizes_modal', methods: ['GET'])]
    public function sizesModal(
        string $slug,
        ClotheService $clotheService,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response
    {
        $variants = $clotheService->getClotheVariantsBySlug($slug);

        if ($variants === []) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        $selectedSizes = array_values(array_filter(array_map(
            fn (ClothesVariant $variant): ?string => $variant->getSize()?->getName(),
            $variants,
        )));
        $stocks = [];
        foreach ($variants as $variant) {
            $sizeName = $variant->getSize()?->getName();
            if ($sizeName !== null) {
                $stocks[$sizeName] = $variant->getStock();
            }
        }

        $html = $this->renderView('clothes/_sizes_modal.html.twig', [
            'slug' => $slug,
            'availableSizes' => ClotheService::AVAILABLE_SIZES,
            'selectedSizes' => $selectedSizes,
            'stocks' => $stocks,
            'action' => $this->generateUrl('app_clothes_sizes_update', ['slug' => $slug]),
            'csrfToken' => $csrfTokenManager->getToken('clothe_sizes_'.$slug)->getValue(),
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    #[Route('/clothes/{slug}/sizes', name: 'app_clothes_sizes_update', methods: ['POST'])]
    public function updateSizes(
        string $slug,
        Request $request,
        ClotheService $clotheService,
        FlashService $flashService,
        LoggerService $logger,
    ): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('clothe_sizes_'.$slug, (string) $request->request->get('_csrf_token'))) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for clothe sizes update.', [
                'slug' => $slug,
            ]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        $selectedSizes = $request->request->all('sizes');
        $stocks = $request->request->all('stocks');
        $confirmDelete = $request->request->getBoolean('confirm_delete');

        try {
            $clotheService->syncClotheSizes(
                $slug,
                is_array($selectedSizes) ? $selectedSizes : [],
                is_array($stocks) ? $stocks : [],
                $confirmDelete,
            );
            $flashService->success('Tailles et stocks mis a jour.');
            $logger->info('Clothe sizes updated.', [
                'slug' => $slug,
            ]);
        } catch (\InvalidArgumentException $exception) {
            $flashService->error($exception->getMessage());
            $logger->warning('Invalid clothe size stock.', [
                'slug' => $slug,
                'error' => $exception->getMessage(),
            ]);
        } catch (\RuntimeException $exception) {
            if ($exception->getMessage() === 'delete_confirmation_required') {
                $flashService->error('Confirme la suppression des tailles retirees avant de valider.');
                $logger->warning('Clothe size update requires delete confirmation.', [
                    'slug' => $slug,
                ]);
            } else {
                $flashService->error('Impossible de modifier les tailles.');
                $logger->exception($exception, 'Unable to update clothe sizes.', [
                    'slug' => $slug,
                ]);
            }
        }

        return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
    }

    private function createShowTabs(string $slug, int $clotheId): array
    {
        return [
            ['id' => 'back', 'label' => 'Retour', 'href' => $this->generateUrl('app_clothes'), 'isActive' => false],
            [
                'id' => 'edit',
                'label' => 'Modifier',
                'href' => $this->generateUrl('app_clothes_edit_modal', ['slug' => $slug]),
                'isActive' => false,
                'attr' => ['data-turbo-frame' => 'clothe-modal-component'],
            ],
            [
                'id' => 'sizes',
                'label' => 'Tailles',
                'href' => $this->generateUrl('app_clothes_sizes_modal', ['slug' => $slug]),
                'isActive' => false,
                'attr' => ['data-turbo-stream' => 'true'],
            ],
            [
                'id' => 'delete',
                'label' => 'Supprimer',
                'href' => $this->generateUrl('app_clothes_delete_modal', ['id' => $clotheId]),
                'isActive' => false,
                'attr' => ['data-turbo-stream' => 'true'],
            ],
        ];
    }

    /**
     * @param list<int> $variantIds
     */
    private function renderClotheBestsellerToggle(
        Clothes $clothe,
        CsrfTokenManagerInterface $csrfTokenManager,
        string $idSuffix = '',
        ?string $slug = null,
        array $variantIds = [],
        ?bool $checked = null,
    ): string
    {
        $payloadIds = $variantIds;
        if ($payloadIds === []) {
            foreach ($clothe->getVariants() as $variant) {
                if ($variant->getId() !== null) {
                    $payloadIds[] = $variant->getId();
                }
            }
        }

        $csrfToken = $csrfTokenManager->getToken('clothe_bestseller')->getValue();
        $onlineAction = new ToggleActionModel(
            url: $this->generateUrl('app_clothes_bestsellers_update'),
            csrfToken: $csrfToken,
            label: 'Bestseller',
        );
        $offlineAction = new ToggleActionModel(
            url: $this->generateUrl('app_clothes_bestsellers_update'),
            csrfToken: $csrfToken,
            label: 'Bestseller',
        );
        $toggle = new ToggleModel(
            id: 'clothe-bestseller-'.$clothe->getId().$idSuffix,
            label: 'Bestseller',
            checked: $checked ?? (bool) $clothe->isBestseller(),
            name: 'bestseller',
            payload: [
                'on' => $onlineAction->toArray() + [
                    'ids' => array_map('strval', $payloadIds),
                    'mode' => 'add',
                ],
                'off' => $offlineAction->toArray() + [
                    'ids' => [],
                    'mode' => 'remove',
                    'slug' => (string) ($slug ?? $clothe->getSlug()),
                ],
            ],
        );

        return $this->renderView('ui/components/toggle/_toggle.html.twig', [
            'toggle' => $toggle->toArray(),
        ]);
    }

    /**
     * @param list<int> $variantIds
     */
    private function renderClotheFeaturedToggle(
        Clothes $clothe,
        CsrfTokenManagerInterface $csrfTokenManager,
        string $idSuffix = '',
        ?string $slug = null,
        array $variantIds = [],
        ?bool $checked = null,
    ): string
    {
        $payloadIds = $variantIds;
        if ($payloadIds === []) {
            foreach ($clothe->getVariants() as $variant) {
                if ($variant->getId() !== null) {
                    $payloadIds[] = $variant->getId();
                }
            }
        }

        $csrfToken = $csrfTokenManager->getToken('clothe_featured')->getValue();
        $onlineAction = new ToggleActionModel(
            url: $this->generateUrl('app_clothes_featured_update'),
            csrfToken: $csrfToken,
            label: 'Mise en avant',
        );
        $offlineAction = new ToggleActionModel(
            url: $this->generateUrl('app_clothes_featured_update'),
            csrfToken: $csrfToken,
            label: 'Mise en avant',
        );
        $toggle = new ToggleModel(
            id: 'clothe-featured-'.$clothe->getId().$idSuffix,
            label: 'Mise en avant',
            checked: $checked ?? (bool) $clothe->isInCarousel(),
            name: 'featured',
            eventName: 'clothe-featured:change',
            payload: [
                'on' => $onlineAction->toArray() + [
                    'ids' => array_map('strval', $payloadIds),
                    'mode' => 'add',
                ],
                'off' => $offlineAction->toArray() + [
                    'ids' => [],
                    'mode' => 'remove',
                    'slug' => (string) ($slug ?? $clothe->getSlug()),
                ],
            ],
        );

        return $this->renderView('ui/components/toggle/_toggle.html.twig', [
            'toggle' => $toggle->toArray(),
        ]);
    }

    private function renderVariantOnlineToggle(ClothesVariant $variant, CsrfTokenManagerInterface $csrfTokenManager): string
    {
        $csrfToken = $csrfTokenManager->getToken($this->getVariantOnlineCsrfTokenId($variant))->getValue();
        $onlineAction = new ToggleActionModel(
            url: $this->generateUrl('app_clothes_variant_toggle_online', [
                'id' => $variant->getId(),
                'state' => 'on',
            ]),
            csrfToken: $csrfToken,
            label: 'En ligne',
        );
        $offlineAction = new ToggleActionModel(
            url: $this->generateUrl('app_clothes_variant_toggle_online', [
                'id' => $variant->getId(),
                'state' => 'off',
            ]),
            csrfToken: $csrfToken,
            label: 'Hors ligne',
        );

        $toggle = new ToggleModel(
            id: 'clothe-variant-online-'.$variant->getId(),
            label: $variant->isOnline() ? 'En ligne' : 'Hors ligne',
            checked: $variant->isOnline(),
            name: 'clothe_variant_online_'.$variant->getId(),
            eventName: 'clothe-online:change',
            payload: [
                'on' => $onlineAction->toArray(),
                'off' => $offlineAction->toArray(),
            ],
        );

        return $this->renderView('ui/components/toggle/_toggle.html.twig', [
            'toggle' => $toggle->toArray(),
        ]);
    }

    private function getOnlineCsrfTokenId(Clothes $clothe): string
    {
        return 'clothe_online_'.((string) $clothe->getId());
    }

    private function getVariantOnlineCsrfTokenId(ClothesVariant $variant): string
    {
        return 'clothe_variant_online_'.((string) $variant->getId());
    }

    /**
     * @return list<int>
     */
    private function readBestsellerIds(Request $request): array
    {
        $payload = $this->readJsonPayload($request);
        $ids = $request->request->all('ids') ?: ($payload['ids'] ?? []);

        if (!is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $id): int => (int) $id,
            $ids,
        ), static fn (int $id): bool => $id > 0)));
    }

    private function readBestsellerMode(Request $request): string
    {
        $payload = $this->readJsonPayload($request);
        $mode = (string) ($request->request->get('mode') ?: ($payload['mode'] ?? 'add'));

        return in_array($mode, ['add', 'replace', 'remove'], true) ? $mode : 'add';
    }

    /**
     * @return list<string>
     */
    private function readBestsellerSlugs(Request $request): array
    {
        $payload = $this->readJsonPayload($request);
        $slugs = $request->request->all('slugs') ?: ($payload['slugs'] ?? []);

        if ((!is_array($slugs) || $slugs === []) && isset($payload['slug'])) {
            $slugs = [$payload['slug']];
        }

        if (!is_array($slugs)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $slug): string => trim((string) $slug),
            $slugs,
        ))));
    }

    private function readBestsellerPruneOverflow(Request $request): bool
    {
        $payload = $this->readJsonPayload($request);

        return $request->request->getBoolean('prune_overflow') || (bool) ($payload['pruneOverflow'] ?? false);
    }

    /**
     * @param list<Clothes> $clothes
     * @return list<string>
     */
    private function extractClotheSlugs(array $clothes): array
    {
        return array_values(array_filter(array_map(
            static fn (Clothes $clothe): ?string => $clothe->getSlug(),
            $clothes,
        )));
    }

    /**
     * @param list<int> $ids
     * @return list<ClothesVariant>
     */
    private function findVariantsByIds(array $ids, EntityManagerInterface $entityManager): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        return $entityManager->getRepository(ClothesVariant::class)->findBy(['id' => $ids]);
    }

    /**
     * @param list<ClothesVariant> $variants
     * @return list<string>
     */
    private function extractVariantSlugs(array $variants): array
    {
        return array_values(array_filter(array_map(
            static fn (ClothesVariant $variant): ?string => $variant->getSlug(),
            $variants,
        )));
    }

    private function readCsrfToken(Request $request): string
    {
        return (string) (
            $request->headers->get('X-CSRF-TOKEN')
            ?: $request->request->get('_csrf_token', '')
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonPayload(Request $request): array
    {
        if (!str_contains((string) $request->headers->get('Content-Type'), 'application/json')) {
            return [];
        }

        $payload = json_decode($request->getContent(), true);

        return is_array($payload) ? $payload : [];
    }

    private function wantsTurboStream(Request $request): bool
    {
        return str_contains((string) $request->headers->get('Accept'), 'text/vnd.turbo-stream.html');
    }

    private function wantsJson(Request $request): bool
    {
        return $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept'), 'application/json');
    }

    /**
     * @param array<string, mixed> $data
     * @param list<UploadedFile> $uploadedImages
     */
    private function createClotheFromPayload(
        array $data,
        array $uploadedImages,
        EntityManagerInterface $entityManager,
        Collections $collection,
        ClotheNameGuard $clotheNameGuard,
    ): void {
        $price = (int) ($data['price'] ?? 0);
        if ($price <= 0) {
            throw new \InvalidArgumentException('Le prix du vetement doit etre superieur a 0.');
        }

        $name = $clotheNameGuard->assertNameAvailable((string) ($data['name'] ?? ''));
        $slug = $clotheNameGuard->createSlug($name);
        $images = $this->storeClotheImages($uploadedImages, $name);
        if ($images === []) {
            throw new \InvalidArgumentException('Ajoute au moins une image pour le vetement.');
        }
        $imagePaths = array_map(static fn (ClotheImageInput $image): string => $image->path, $images);

        $now = new \DateTimeImmutable();
        $clothe = (new Clothes())
            ->setName($name)
            ->setPrice($price)
            ->setCollection($collection)
            ->setIsOnline(false)
            ->setCreatedAt($now)
            ->setEditedAt($now);

        $variantPayloads = $this->normalizeVariantPayloads($data);
        if ($variantPayloads === []) {
            throw new \InvalidArgumentException('Ajoute au moins une variante au vetement.');
        }

        foreach ($variantPayloads as $variantData) {
            $stock = filter_var($variantData['stock'] ?? 0, FILTER_VALIDATE_INT);
            if ($stock === false || $stock < 0) {
                throw new \InvalidArgumentException('Le stock de chaque variante doit etre un entier positif ou nul.');
            }

            $color = $this->resolveClotheColorFromPayload($variantData, $entityManager);
            if (!$color instanceof Clothescolor) {
                throw new \InvalidArgumentException('Selectionne une couleur pour chaque variante.');
            }

            $sizeName = trim((string) ($variantData['size'] ?? ''));
            if (!in_array($sizeName, ClotheService::AVAILABLE_SIZES, true)) {
                throw new \InvalidArgumentException('Selectionne une taille valide pour chaque variante.');
            }

            $metaDescription = $this->normalizeVariantMetaDescription($variantData['metadescription'] ?? null);
            $size = $this->findOrCreateSize($sizeName, $entityManager);
            $variantName = $this->createVariantName($name, $color, $size);
            $variant = (new ClothesVariant())
                ->setName($variantName)
                ->setSlug($this->createVariantSlug($name, $color))
                ->setStock($stock)
                ->setColor($color)
                ->setSize($size)
                ->setSku($this->createVariantSku($clothe, $color, $size))
                ->setDescription(trim((string) ($data['description'] ?? '')) ?: null)
                ->setMetadescription($metaDescription)
                ->setImages($imagePaths)
                ->setHighlightImage($imagePaths[0] ?? null)
                ->setBestsellerImage($imagePaths[0] ?? null)
                ->setIsOnline((bool) ($variantData['isOnline'] ?? false));

            $clothe->addVariant($variant);
        }

        $this->assertUniqueVariantPayload($clothe, $entityManager);
        $entityManager->persist($clothe);
    }

    /**
     * @param array<string, mixed> $data
     * @return list<array<string, mixed>>
     */
    private function normalizeVariantPayloads(array $data): array
    {
        $variants = $data['variants'] ?? [];
        if (is_array($variants) && $variants !== []) {
            $normalizedVariants = [];

            foreach ($variants as $variant) {
                if (!is_array($variant)) {
                    continue;
                }

                $selectedSizes = $variant['sizes'] ?? ($variant['size'] ?? []);
                if (is_string($selectedSizes)) {
                    $selectedSizes = [$selectedSizes];
                }

                $sizes = array_values(array_intersect(
                    ClotheService::AVAILABLE_SIZES,
                    is_array($selectedSizes) ? $selectedSizes : [],
                ));

                foreach ($sizes as $size) {
                    $variant['size'] = $size;
                    $normalizedVariants[] = $variant;
                }
            }

            return $normalizedVariants;
        }

        $selectedSizes = $data['sizes'] ?? [];
        $sizes = array_values(array_intersect(ClotheService::AVAILABLE_SIZES, is_array($selectedSizes) ? $selectedSizes : []));

        $legacyVariants = array_map(
            static fn (string $size): array => [
                'color' => $data['color'] ?? null,
                'newColorName' => $data['newColorName'] ?? null,
                'newColorHex' => $data['newColorHex'] ?? null,
                'size' => $size,
                'stock' => $data['stock'] ?? 0,
                'sku' => null,
                'isOnline' => false,
                'metadescription' => $data['metadescription'] ?? null,
            ],
            $sizes,
        );

        return $legacyVariants;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveClotheColorFromPayload(array $data, EntityManagerInterface $entityManager): ?Clothescolor
    {
        $newColorName = trim((string) ($data['newColorName'] ?? ''));
        if ($newColorName !== '') {
            $colorHex = ltrim(trim((string) ($data['newColorHex'] ?? '')), '#');
            if ($colorHex !== '' && !preg_match('/^[a-fA-F0-9]{6}$/', $colorHex)) {
                throw new \InvalidArgumentException('Le code couleur doit etre au format hexadecimal.');
            }

            $existingColor = $entityManager->getRepository(Clothescolor::class)->findOneBy(['name' => $newColorName]);
            if ($existingColor instanceof Clothescolor) {
                return $existingColor;
            }

            $color = (new Clothescolor())
                ->setName($newColorName)
                ->setHexa($colorHex !== '' ? strtolower($colorHex) : null)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setEditedAt(new \DateTimeImmutable());

            $entityManager->persist($color);

            return $color;
        }

        if ((string) ($data['color'] ?? '') === '__new__') {
            return null;
        }

        $color = $entityManager->getRepository(Clothescolor::class)->find((int) ($data['color'] ?? 0));

        return $color instanceof Clothescolor ? $color : null;
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

    /**
     * @param list<UploadedFile> $uploadedImages
     * @return list<ClotheImageInput>
     */
    private function storeClotheImages(array $uploadedImages, string $clotheName): array
    {
        $directorySlug = strtolower((string) (new AsciiSlugger())->slug($clotheName));
        $directory = $this->getParameter('kernel.project_dir').'/public/images/upload/clothes/'.$directorySlug;
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create clothe upload directory.');
        }

        $images = [];
        foreach (array_values($uploadedImages) as $position => $image) {
            if (!$image instanceof UploadedFile || !$this->isValidClotheImage($image)) {
                continue;
            }

            $extension = strtolower((string) $image->guessExtension());
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }

            if ($extension === '' || !in_array($extension, self::IMAGE_EXTENSIONS, true)) {
                $extension = strtolower((string) $image->getClientOriginalExtension());
            }

            $filename = sprintf('%02d-%s.%s', $position + 1, bin2hex(random_bytes(4)), $extension);
            $image->move($directory, $filename);
            $images[] = new ClotheImageInput(
                path: '/images/upload/clothes/'.$directorySlug.'/'.$filename,
                originalName: (string) $image->getClientOriginalName(),
                position: $position,
            );
        }

        return $images;
    }

    private function isValidClotheImage(UploadedFile $image): bool
    {
        return in_array(strtolower((string) $image->getClientOriginalExtension()), self::IMAGE_EXTENSIONS, true)
            && in_array((string) $image->getMimeType(), self::IMAGE_MIME_TYPES, true);
    }

    #[Route('/clothes/add', name: 'app_clothe_add', methods: ['GET', 'POST'], priority: 20)]
    public function add(
        Request $request,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        ClotheNameGuard $clotheNameGuard,
        FlashService $flashService,
        LoggerService $logger,
    ): Response {
        if ($request->isMethod('POST')) {
            $token = new CsrfToken('clothe_create', (string) $request->request->get('_csrf_token', ''));

            if (!$csrfTokenManager->isTokenValid($token)) {
                $flashService->error('Token CSRF invalide.');
                $logger->warning('Invalid CSRF token for clothe creation.');

                return $this->redirectToRoute('app_clothe_add');
            }

            $collection = $entityManager->getRepository(Collections::class)->find($request->request->getInt('collection'));
            if (!$collection instanceof Collections) {
                $flashService->error('Selectionne une collection.');
                $logger->warning('Invalid collection selected for clothe creation.', [
                    'collection_id' => $request->request->getInt('collection'),
                ]);

                return $this->redirectToRoute('app_clothe_add');
            }

            try {
                $this->createClotheFromPayload(
                    data: $request->request->all('clothe'),
                    uploadedImages: $request->files->all('clotheImages'),
                    entityManager: $entityManager,
                    collection: $collection,
                    clotheNameGuard: $clotheNameGuard,
                );
                $entityManager->flush();
                $flashService->success('Vetement cree hors-ligne.');
                $logger->info('Clothe created.', [
                    'collection_id' => $collection->getId(),
                ]);
            } catch (\InvalidArgumentException $exception) {
                $flashService->error($exception->getMessage());
                $logger->warning('Clothe creation rejected.', [
                    'error' => $exception->getMessage(),
                    'collection_id' => $collection->getId(),
                ]);

                return $this->redirectToRoute('app_clothe_add');
            }

            return $this->redirectToRoute('app_clothes');
        }

        return $this->render('clothes/add.html.twig', [
            'breadscrumbs' => [
                ['label' => 'Dashboard', 'route' => 'app_dashboard'],
                ['label' => 'Vêtements', 'route' => 'app_clothes'],
                ['label' => 'Ajouter'],
            ],
            'tabs' => [
                ['id' => 'back', 'label' => 'Retour', 'href' => $this->generateUrl('app_clothes'), 'isActive' => false],
            ],
            'action' => $this->generateUrl('app_clothe_add'),
            'csrfToken' => $csrfTokenManager->getToken('clothe_create')->getValue(),
            'collections' => $entityManager->getRepository(Collections::class)->findBy([], ['name' => 'ASC']),
            'colors' => $entityManager->getRepository(Clothescolor::class)->findBy([], ['name' => 'ASC']),
            'availableSizes' => ClotheService::AVAILABLE_SIZES,
        ]);
    }

    /**
     * @param list<ClothesVariant> $variants
     */
    private function mapClotheDetails(
        Clothes $mainClothe,
        array $variants,
        ClotheOnlineGuard $clotheOnlineGuard,
        ClotheSizeGuideService $clotheSizeGuideService,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): array
    {
        $images = [];
        $sizes = [];
        $colors = [];
        $variantGroups = [];
        $highlightImage = null;
        $bestsellerImage = null;
        $metadescription = null;

        foreach ($variants as $variant) {
            if (!$variant instanceof ClothesVariant) {
                continue;
            }

            $sizeName = $variant->getSize()?->getName() ?? 'Taille inconnue';
            $colorName = $variant->getColor()?->getName() ?? 'Couleur inconnue';
            $colors[$colorName] = $colorName;
            $images = array_merge($images, $variant->getImages() ?? []);
            $highlightImage ??= $variant->getHighlightImage();
            $bestsellerImage ??= $variant->getBestsellerImage();
            $metadescription ??= $variant->getMetadescription();
            $sizeView = [
                'id' => $variant->getId(),
                'name' => $sizeName,
                'color' => $colorName,
                'sku' => $variant->getSku(),
                'description' => $variant->getDescription() ?? $mainClothe->getDescription(),
                'metadescription' => $variant->getMetadescription(),
                'images' => $variant->getImages() ?? [],
                'highlightImage' => $variant->getHighlightImage(),
                'bestsellerImage' => $variant->getBestsellerImage(),
                'stock' => $variant->getStock(),
                'isOnline' => $variant->isOnline(),
                'isAvailable' => $variant->isAvailable(),
                'editUrl' => $this->generateUrl('app_clothes_variant_edit_modal', ['id' => $variant->getId()]),
                'onlineToggle' => $this->renderVariantOnlineToggle($variant, $csrfTokenManager),
            ];
            $sizes[] = $sizeView;

            $colorId = $variant->getColor()?->getId();
            $groupKey = $colorId !== null ? (string) $colorId : $colorName;
            if (!isset($variantGroups[$groupKey])) {
                $idSuffix = '-variant-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $groupKey);
                $variantGroups[$groupKey] = [
                    'colorId' => $colorId,
                    'color' => $colorName,
                    'slug' => $variant->getSlug(),
                    'sizes' => [],
                    'variantIds' => [],
                    'images' => [],
                    'highlightImage' => null,
                    'bestsellerImage' => null,
                    'isBestseller' => false,
                    'isInCarousel' => false,
                    'editUrl' => $colorId !== null
                        ? $this->generateUrl('app_clothes_variant_group_edit_modal', [
                            'slug' => $variant->getSlug(),
                            'color' => $colorId,
                        ])
                        : $this->generateUrl('app_clothes_variant_edit_modal', ['id' => $variant->getId()]),
                    'imagesModalUrl' => $this->generateUrl('app_clothes_images_modal', array_filter([
                        'slug' => $variant->getSlug(),
                        'color' => $colorId,
                    ], static fn (mixed $value): bool => $value !== null)),
                    'bestsellerToggle' => '',
                    'featuredToggle' => '',
                    'toggleIdSuffix' => $idSuffix,
                ];
            }

            $variantGroups[$groupKey]['sizes'][] = $sizeView;
            $variantGroups[$groupKey]['variantIds'][] = $variant->getId();
            $variantGroups[$groupKey]['images'] = [
                ...$variantGroups[$groupKey]['images'],
                ...($variant->getImages() ?? []),
            ];
            $variantGroups[$groupKey]['highlightImage'] ??= $variant->getHighlightImage();
            $variantGroups[$groupKey]['bestsellerImage'] ??= $variant->getBestsellerImage();
            $variantGroups[$groupKey]['isBestseller'] = $variantGroups[$groupKey]['isBestseller'] || $variant->isBestseller();
            $variantGroups[$groupKey]['isInCarousel'] = $variantGroups[$groupKey]['isInCarousel'] || $variant->isInCarousel();
        }

        foreach ($variantGroups as &$group) {
            $group['images'] = array_values(array_unique(array_filter($group['images'])));
            $group['variantIds'] = array_values(array_unique(array_filter($group['variantIds'])));
            $group['bestsellerToggle'] = $this->renderClotheBestsellerToggle(
                $mainClothe,
                $csrfTokenManager,
                (string) $group['toggleIdSuffix'],
                (string) $group['slug'],
                $group['variantIds'],
                (bool) $group['isBestseller'],
            );
            $group['featuredToggle'] = $this->renderClotheFeaturedToggle(
                $mainClothe,
                $csrfTokenManager,
                (string) $group['toggleIdSuffix'],
                (string) $group['slug'],
                $group['variantIds'],
                (bool) $group['isInCarousel'],
            );
            unset($group['toggleIdSuffix']);
        }
        unset($group);

        return [
            'id' => $mainClothe->getId(),
            'name' => $mainClothe->getName(),
            'slug' => $variants[0]?->getSlug() ?? $mainClothe->getSlug(),
            'description' => $mainClothe->getDescription(),
            'metadescription' => $metadescription,
            'collection' => $mainClothe->getCollection()?->getName(),
            'collectionId' => $mainClothe->getCollection()?->getId(),
            'category' => $mainClothe->getCollection()?->getCategory()?->getName(),
            'categoryId' => $mainClothe->getCollection()?->getCategory()?->getId(),
            'color' => implode(', ', array_values($colors)),
            'price' => $mainClothe->getPrice(),
            'status' => $mainClothe->getStatus(),
            'isOnline' => $clotheOnlineGuard->isOnline($variants),
            'isBestseller' => (bool) $mainClothe->isBestseller(),
            'isInCarousel' => (bool) $mainClothe->isInCarousel(),
            'totalStock' => $mainClothe->getTotalStock(),
            'hasAvailableVariant' => $mainClothe->hasAvailableVariant(),
            'highlightImage' => $highlightImage,
            'bestsellerImage' => $bestsellerImage,
            'images' => array_values(array_unique(array_filter($images))),
            'sizes' => $sizes,
            'variantGroups' => array_values($variantGroups),
            'sizeGuide' => $clotheSizeGuideService->buildView($mainClothe, $variants),
        ];
    }

    /**
     * @param list<ClothesVariant> $variants
     */
    private function resolveMainClothe(array $variants): Clothes
    {
        $firstVariant = $variants[0] ?? null;
        $clothe = $firstVariant instanceof ClothesVariant ? $firstVariant->getClothes() : null;

        if (!$clothe instanceof Clothes) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        return $clothe;
    }

    private function assertUniqueVariantPayload(Clothes $clothe, ?EntityManagerInterface $entityManager = null): void
    {
        $combinations = [];
        $skus = [];

        foreach ($clothe->getVariants() as $variant) {
            $colorName = (string) $variant->getColor()?->getName();
            $sizeName = (string) $variant->getSize()?->getName();
            $combinationKey = mb_strtolower($colorName.'|'.$sizeName);
            $skuKey = mb_strtolower((string) $variant->getSku());

            if (isset($combinations[$combinationKey])) {
                throw new \InvalidArgumentException(sprintf(
                    'Une variante existe deja pour la couleur %s et la taille %s.',
                    $colorName,
                    $sizeName,
                ));
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

    /**
     * @param list<ClothesVariant> $variants
     * @return list<ClothesVariant>
     */
    private function filterVariantsByColor(array $variants, ?int $colorId): array
    {
        if ($colorId === null) {
            return $variants;
        }

        return array_values(array_filter(
            $variants,
            static fn (ClothesVariant $variant): bool => $variant->getColor()?->getId() === $colorId,
        ));
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

    private function createVariantName(string $name, Clothescolor $color, Clothessize $size): string
    {
        return trim(sprintf('%s %s %s', $name, (string) $color->getName(), (string) $size->getName()));
    }

    private function createVariantSlug(string $name, Clothescolor $color): string
    {
        return strtolower((string) (new AsciiSlugger())->slug(trim(sprintf('%s %s', $name, (string) $color->getName()))));
    }

    private function normalizeVariantMetaDescription(mixed $value): ?string
    {
        $metaDescription = trim((string) $value);
        if (mb_strlen($metaDescription) > 200) {
            throw new \InvalidArgumentException('La meta description est limitee a 200 caracteres.');
        }

        return $metaDescription !== '' ? $metaDescription : null;
    }
}
