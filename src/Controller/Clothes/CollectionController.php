<?php

namespace App\Controller\Clothes;

use App\Application\Clothes\Services\ClothesCreationService;
use App\Application\Clothes\Services\ClotheService;
use App\Application\Clothes\Services\CollectionCreationService;
use App\Application\Clothes\Services\CollectionPublicationService;
use App\Entity\Category\Category;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Collections\Collections;
use App\Notifier\Services\FlashService;
use App\Service\LoggerService;
use App\UI\Toggle\ToggleActionModel;
use App\UI\Toggle\ToggleModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class CollectionController extends AbstractController
{
    private const COLUMNS = [
        ['key' => 'name', 'label' => 'Nom', 'sortable' => true],
        ['key' => 'category', 'label' => 'Categorie', 'sortable' => true],
        ['key' => 'clothesCount', 'label' => 'Vetements', 'sortable' => true],
        ['key' => 'actions', 'label' => 'Actions', 'sortable' => false, 'raw' => true],
    ];

    private const SORTS = [
        'id' => 'col.id',
        'name' => 'col.name',
        'category' => 'cat.name',
        'isOnline' => 'col.isOnline',
        'clothesCount' => 'clothesCount',
        'createdAt' => 'col.createdAt',
    ];

    #[Route('/collections', name: 'app_clothe_collection', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response
    {
        return $this->render('clothes/collections/index.html.twig', [
            'breadscrumbs' => $this->createBreadscrumbs('Collections'),
            'tabs' => $this->createTabs(),
            'table' => $this->createTableData($request, $entityManager, $csrfTokenManager),
        ]);
    }

    #[Route('/collections/table', name: 'app_clothe_collection_table', methods: ['GET'])]
    public function table(
        Request $request,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse
    {
        $table = $this->createTableData($request, $entityManager, $csrfTokenManager);

        return $this->json([
            'html' => $this->renderView('ui/components/data-table/_rows.html.twig', [
                'columns' => $table['columns'],
                'rows' => $table['rows'],
            ]),
        ]);
    }

    #[Route('/collections/add', name: 'app_clothe_collection_add', methods: ['GET', 'POST'])]
    public function add(
        Request $request,
        EntityManagerInterface $entityManager,
        CollectionCreationService $collectionCreationService,
        ClothesCreationService $clothesCreationService,
        LoggerService $logger,
        FlashService $flashService,
    ): Response {
        //todo >>>>>>>>>>>>>>>>>
        //todo : add symfony like form validation (isValid/isSubmitted) and use form type for collection and clothes
        if ($request->isMethod('POST')) {
            $token = (string) $request->getPayload()->get('_token');

            if (!$this->isCsrfTokenValid('collection_create', $token)) {
                $flashService->error('Token CSRF invalide.');
                $logger->warning('Invalid CSRF token for collection creation.');

                return $this->redirectToRoute('app_clothe_collection_add');
            }

            try {
                $collection = $collectionCreationService->createFromRequest($request);
                $clothesCreationService->createForCollectionFromRequest($request, $collection);
            } catch (\InvalidArgumentException $exception) {
                $flashService->error($exception->getMessage());
                $logger->warning('Collection creation rejected.', [
                    'error' => $exception->getMessage(),
                ]);

                return $this->redirectToRoute('app_clothe_collection_add');
            }

            $flashService->success('Collection creee hors-ligne.');
            $logger->info('Collection created.', [
                'collection_id' => $collection->getId(),
                'collection_name' => $collection->getName(),
            ]);

            return $this->redirectToRoute('app_clothe_collection');
        }
        //todo <<<<<<<<<<<<<<<<<
        
        return $this->render('clothes/collections/add.html.twig', [
            'breadscrumbs' => $this->createBreadscrumbs('Ajouter une collection'),
            'tabs' => [
                [
                    'id' => 'back',
                    'label' => 'Retour',
                    'href' => $this->generateUrl('app_clothe_collection'),
                    'isActive' => false,
                ],
            ],
            'action' => $this->generateUrl('app_clothe_collection_add'),
            'categories' => $entityManager->getRepository(Category::class)->findBy([], ['name' => 'ASC']),
            'colors' => $entityManager->getRepository(Clothescolor::class)->findBy([], ['name' => 'ASC']),
            'availableSizes' => ClotheService::AVAILABLE_SIZES,
        ]);
    }


    #[Route('/collections/{id}', name: 'app_clothes_collection', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Collections $collection, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        return $this->render('clothes/collections/show.html.twig', [
            'breadscrumbs' => [
                ['label' => 'Dashboard', 'route' => 'app_dashboard'],
                ['label' => 'Vêtements', 'route' => 'app_clothes'],
                ['label' => 'Collections', 'route' => 'app_clothe_collection'],
                ['label' => (string) $collection->getName()],
            ],
            'tabs' => [
                [
                    'id' => 'back',
                    'label' => 'Retour',
                    'href' => $this->generateUrl('app_clothe_collection'),
                    'isActive' => false,
                ],
            ],
            'collection' => $collection,
            // dd($collection->getClothes()->toArray()),
            'clothes' => $this->mapDistinctClothes($collection),
            'onlineToggle' => $this->renderCollectionOnlineToggle($collection, $csrfTokenManager),
        ]);
    }

    #[Route('/collections/{id}/clothes', name: 'app_clothes_collection_clothes', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function clothes(Collections $collection): Response
    {
        return $this->render('clothes/collections/_clothes_list.html.twig', [
            'clothes' => $this->mapDistinctClothes($collection),
        ]);
    }

    #[Route('/collections/{id}/online/{state}', name: 'app_clothes_collection_toggle_online', requirements: ['id' => '\d+', 'state' => 'on|off'], methods: ['POST'])]
    public function toggleOnline(
        Collections $collection,
        string $state,
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
        CollectionPublicationService $collectionPublicationService,
        LoggerService $logger,
    ): JsonResponse {
        $token = new CsrfToken($this->getOnlineCsrfTokenId($collection), (string) $request->headers->get('X-CSRF-TOKEN', ''));

        if (!$csrfTokenManager->isTokenValid($token)) {
            $logger->warning('Invalid CSRF token for collection online toggle.', [
                'collection_id' => $collection->getId(),
                'state' => $state,
            ]);

            return $this->json(['success' => false, 'error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        if ($state === 'on' && !$collectionPublicationService->publish($collection)) {
            $logger->warning('Collection publication rejected.', [
                'collection_id' => $collection->getId(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Collection cannot be published.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($state === 'off') {
            $collectionPublicationService->unpublish($collection);
        }

        return $this->json([
            'success' => true,
            'isOnline' => $collection->isOnline(),
            'clothesFrameUrl' => $this->generateUrl('app_clothes_collection_clothes', [
                'id' => $collection->getId(),
            ]),
        ]);
    }

    #[Route('/collections/{id}/delete', name: 'app_clothe_collection_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Collections $collection,
        Request $request,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        LoggerService $logger,
    ): Response {
        $token = new CsrfToken($this->getDeleteCsrfTokenId($collection), (string) $request->request->get('_csrf_token', ''));

        if (!$csrfTokenManager->isTokenValid($token)) {
            $logger->warning('Invalid CSRF token for collection deletion.', [
                'collection_id' => $collection->getId(),
            ]);

            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        $rowId = $this->getCollectionRowId($collection);

        $entityManager->remove($collection);
        $entityManager->flush();
        $logger->info('Collection deleted.', [
            'collection_id' => $collection->getId(),
            'row_id' => $rowId,
        ]);

        return new Response(
            sprintf('<turbo-stream action="remove" target="%s"></turbo-stream>', $rowId),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    private function createTableData(
        Request $request,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): array
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = $this->normalizeSort((string) $request->query->get('sort', 'name'));
        $direction = $this->normalizeDirection((string) $request->query->get('direction', 'asc'));

        $queryBuilder = $entityManager->getRepository(Collections::class)
            ->createQueryBuilder('col')
            ->select('col, cat, COUNT(cl.id) AS clothesCount')
            ->leftJoin('col.category', 'cat')
            ->leftJoin('col.clothes', 'cl')
            ->groupBy('col.id')
            ->addGroupBy('cat.id')
            ->orderBy(self::SORTS[$sort], $direction);

        if ($search !== '') {
            $queryBuilder
                ->andWhere('LOWER(col.name) LIKE :search OR LOWER(cat.name) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($search).'%');
        }

        $results = $queryBuilder
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();

        return [
            'columns' => self::COLUMNS,
            'rows' => array_map(fn (array $result): array => $this->mapCollection($result, $csrfTokenManager), $results),
            'url' => $this->generateUrl('app_clothe_collection_table'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ];
    }

    private function mapCollection(array $result, CsrfTokenManagerInterface $csrfTokenManager): array
    {
        $collection = $result[0] ?? null;

        if (!$collection instanceof Collections) {
            return [];
        }

        return [
            '_rowId' => $this->getCollectionRowId($collection),
            'name' => (string) $collection->getName(),
            'category' => (string) $collection->getCategory()?->getName(),
            'clothesCount' => (string) ($result['clothesCount'] ?? 0),
            'actions' => $this->renderCollectionActions($collection, $csrfTokenManager),
        ];
    }

    private function createTabs(): array
    {
        return [
            [
                'id' => 'add',
                'label' => 'Ajouter une collection',
                'href' => $this->generateUrl('app_clothe_collection_add'),
                'isActive' => false,
            ],
            [
                'id' => 'back',
                'label' => 'Retour',
                'href' => $this->generateUrl('app_clothes'),
                'isActive' => false,
            ],
        ];
    }

    private function renderCollectionActions(Collections $collection, CsrfTokenManagerInterface $csrfTokenManager): string
    {
        return $this->renderView('clothes/collections/_actions.html.twig', [
            'collectionId' => $collection->getId(),
            'showUrl' => $this->generateUrl('app_clothes_collection', ['id' => $collection->getId()]),
            'deleteUrl' => $this->generateUrl('app_clothe_collection_delete', ['id' => $collection->getId()]),
            'deleteToken' => $csrfTokenManager->getToken($this->getDeleteCsrfTokenId($collection))->getValue(),
        ]);
    }

    private function renderCollectionOnlineToggle(Collections $collection, CsrfTokenManagerInterface $csrfTokenManager): string
    {
        $csrfToken = $csrfTokenManager->getToken($this->getOnlineCsrfTokenId($collection))->getValue();
        $onlineAction = new ToggleActionModel(
            url: $this->generateUrl('app_clothes_collection_toggle_online', [
                'id' => $collection->getId(),
                'state' => 'on',
            ]),
            csrfToken: $csrfToken,
            label: 'En ligne',
        );
        $offlineAction = new ToggleActionModel(
            url: $this->generateUrl('app_clothes_collection_toggle_online', [
                'id' => $collection->getId(),
                'state' => 'off',
            ]),
            csrfToken: $csrfToken,
            label: 'Hors ligne',
        );

        $toggle = new ToggleModel(
            id: 'collection-online-'.$collection->getId(),
            label: $collection->isOnline() ? 'En ligne' : 'Hors ligne',
            checked: (bool) $collection->isOnline(),
            name: 'collection_online_'.$collection->getId(),
            payload: [
                'on' => $onlineAction->toArray(),
                'off' => $offlineAction->toArray(),
            ],
        );

        return $this->renderView('ui/components/toggle/_toggle.html.twig', [
            'toggle' => $toggle->toArray(),
        ]);
    }

    /**
     * @return list<array{
     *     name: string,
     *     slug: string,
     *     image: ?string,
     *     sizes: list<array{name: string, isOnline: bool, stock: int}>
     * }>
     */
    private function mapDistinctClothes(Collections $collection): array
    {
        $items = [];

        foreach ($collection->getClothes() as $clothe) {
            if (!$clothe instanceof Clothes) {
                continue;
            }

            $slug = (string) $clothe->getSlug();
            if ($slug === '') {
                $slug = 'clothe-'.$clothe->getId();
            }

            if (!isset($items[$slug])) {
                $images = $clothe->getImages() ?? [];
                $items[$slug] = [
                    'name' => (string) $clothe->getName(),
                    'slug' => $slug,
                    'image' => $images[0] ?? $collection->getImage(),
                    'sizes' => [],
                ];
            }

            $size = $clothe->getSize()?->getName();
            if ($size !== null && $size !== '') {
                $items[$slug]['sizes'][$size] = [
                    'name' => $size,
                    'isOnline' => (bool) $clothe->isOnline(),
                    'stock' => $clothe->getStock() ?? 0,
                ];
            }
        }

        foreach ($items as &$item) {
            $item['sizes'] = array_values($item['sizes']);
            usort(
                $item['sizes'],
                fn (array $a, array $b): int => $this->sortSize($a['name']) <=> $this->sortSize($b['name']),
            );
        }
        unset($item);

        return array_values($items);
    }

    private function sortSize(string $size): int
    {
        $index = array_search($size, ClotheService::AVAILABLE_SIZES, true);

        return $index === false ? 999 : $index;
    }

    /**
     * @return list<array{label: string, route?: string}>
     */
    private function createBreadscrumbs(string $currentLabel): array
    {
        return [
            ['label' => 'Dashboard', 'route' => 'app_dashboard'],
            ['label' => 'Vêtements', 'route' => 'app_clothes'],
            ['label' => $currentLabel],
        ];
    }

    private function getDeleteCsrfTokenId(Collections $collection): string
    {
        return 'collection_delete_'.((string) $collection->getId());
    }

    private function getOnlineCsrfTokenId(Collections $collection): string
    {
        return 'collection_online_'.((string) $collection->getId());
    }

    private function getCollectionRowId(Collections $collection): string
    {
        return 'collection-row-'.((string) $collection->getId());
    }

    private function normalizeSort(string $sort): string
    {
        return array_key_exists($sort, self::SORTS) ? $sort : 'name';
    }

    private function normalizeDirection(string $direction): string
    {
        return strtolower($direction) === 'desc' ? 'desc' : 'asc';
    }
}
