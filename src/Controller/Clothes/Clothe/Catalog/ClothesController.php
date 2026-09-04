<?php

namespace App\Controller\Clothes\Clothe\Catalog;

use App\Application\Clothes\Mapper\ClotheProductGridItemMapper;
use App\Application\Clothes\Services\Clothe\ClotheService;
use App\Entity\Category\Category;
use App\Entity\Collections\Collections;
use App\Enum\ClotheStatus;
use App\Service\BreadscrumbsService;
use App\UI\ProductGrid\ProductGridFilterModel;
use App\UI\ProductGrid\ProductGridViewModel;
use App\UI\Tabs\TabsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class ClothesController extends AbstractController
{
    #[Route('/clothes', name: 'app_clothes', methods: ['GET'])]
    public function index(
        Request $request,
        BreadscrumbsService $breadscrumbs,
        TabsService $tabsService,
        ClotheService $clotheService,
        ClotheProductGridItemMapper $productGridItemMapper,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $category = $request->query->getInt('category') ?: null;
        $collection = $request->query->getInt('collection') ?: null;
        $bestsellerOnly = $request->query->getBoolean('bestseller');
        $status = ClotheStatus::tryFrom((string) $request->query->get('status'));
        $variantGroups = $clotheService->getDistinctClotheByName(
            sortBy: 'name',
            direction: 'asc',
            query: (string) $request->query->get('search', ''),
            category: $category,
            collection: $collection,
            bestsellerOnly: $bestsellerOnly,
            status: $status,
        );

        return $this->render('clothes/index.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve((string) $request->attributes->get('_route')),
            'tabs' => $tabsService->create(),
            'gridData' => new ProductGridViewModel(
                id: 'clothes-grid',
                items: array_map(
                    $productGridItemMapper->mapVariantGroup(...),
                    $variantGroups,
                ),
                filters: $this->createFilters(
                    categories: $clotheService->getCategoriesByName(),
                    collections: $clotheService->getCollectionsByName(),
                    selectedCategory: $category,
                    selectedCollection: $collection,
                    selectedStatus: $status,
                ),
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
                extraSearchParams: array_filter([
                    'bestseller' => $bestsellerOnly ? '1' : null,
                ]),
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
                    'status' => $status?->value,
                ],
                part: 'clothes',
            ),
        ]);
    }

    /**
     * @param list<Category>    $categories
     * @param list<Collections> $collections
     *
     * @return list<ProductGridFilterModel>
     */
    private function createFilters(
        array $categories,
        array $collections,
        ?int $selectedCategory,
        ?int $selectedCollection,
        ?ClotheStatus $selectedStatus,
    ): array {
        return [
            new ProductGridFilterModel(
                id: 'category',
                label: 'Categorie',
                options: $this->createOptions(
                    $categories,
                    'Toutes les categories',
                ),
                selected: null !== $selectedCategory ? (string) $selectedCategory : null,
            ),
            new ProductGridFilterModel(
                id: 'collection',
                label: 'Collection',
                options: $this->createOptions(
                    $collections,
                    'Toutes les collections',
                ),
                selected: null !== $selectedCollection ? (string) $selectedCollection : null,
            ),
            new ProductGridFilterModel(
                id: 'status',
                label: 'Statut de publication',
                options: [
                    ['value' => '', 'label' => 'Tous les statuts'],
                    ...array_map(
                        static fn (ClotheStatus $status): array => [
                            'value' => $status->value,
                            'label' => $status->label(),
                        ],
                        ClotheStatus::cases(),
                    ),
                ],
                selected: $selectedStatus?->value,
            ),
        ];
    }

    /**
     * @param list<Category|Collections> $entities
     *
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

}
