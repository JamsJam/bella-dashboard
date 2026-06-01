<?php

namespace App\Controller\Clothes;

use App\Application\Clothes\DTO\ClotheImageInput;
use App\Application\Clothes\Services\ClotheService;
use App\Application\Clothes\Services\CollectionPublicationService;
use App\Entity\Category\Category;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use App\Entity\Collections\Collections;
use App\Notifier\Services\FlashService;
use App\UI\Toggle\ToggleActionModel;
use App\UI\Toggle\ToggleModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class CollectionController extends AbstractController
{
    private const ILLUSTRATION_EXTENSIONS = ['png', 'jpg', 'jpeg'];
    private const ILLUSTRATION_MIME_TYPES = ['image/png', 'image/jpeg'];

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
        CsrfTokenManagerInterface $csrfTokenManager,
        FlashService $flashService,
    ): Response {
        if ($request->isMethod('POST')) {
            $token = new CsrfToken('collection_create', (string) $request->request->get('_csrf_token', ''));

            if (!$csrfTokenManager->isTokenValid($token)) {
                $flashService->error('Token CSRF invalide.');

                return $this->redirectToRoute('app_clothe_collection_add');
            }

            $name = trim((string) $request->request->get('name', ''));
            if ($name === '') {
                $flashService->error('Le nom de la collection est obligatoire.');

                return $this->redirectToRoute('app_clothe_collection_add');
            }

            $image = $request->files->get('illustration');
            if (!$image instanceof UploadedFile || !$this->isValidIllustration($image)) {
                $flashService->error('Image invalide. Formats acceptes : PNG ou JPEG.');

                return $this->redirectToRoute('app_clothe_collection_add');
            }

            $category = $this->resolveCategory($request, $entityManager);
            if (!$category instanceof Category) {
                $flashService->error('Selectionne une categorie ou cree une nouvelle categorie.');

                return $this->redirectToRoute('app_clothe_collection_add');
            }

            $collection = (new Collections())
                ->setName($name)
                ->setCategory($category)
                ->setIsOnline(false)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setEditedAt(new \DateTimeImmutable());

            $entityManager->persist($collection);
            $entityManager->flush();

            $collection->setImage($this->storeIllustration($collection, $image));

            try {
                $this->createRequestedClothesForCollection($request, $entityManager, $collection);
            } catch (\InvalidArgumentException $exception) {
                $flashService->error($exception->getMessage());

                return $this->redirectToRoute('app_clothe_collection_add');
            }

            $entityManager->flush();

            $flashService->success('Collection creee hors-ligne.');

            return $this->redirectToRoute('app_clothe_collection');
        }

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
            'csrfToken' => $csrfTokenManager->getToken('collection_create')->getValue(),
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
    ): JsonResponse {
        $token = new CsrfToken($this->getOnlineCsrfTokenId($collection), (string) $request->headers->get('X-CSRF-TOKEN', ''));

        if (!$csrfTokenManager->isTokenValid($token)) {
            return $this->json(['success' => false, 'error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        if ($state === 'on' && !$collectionPublicationService->publish($collection)) {
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
    ): Response {
        $token = new CsrfToken($this->getDeleteCsrfTokenId($collection), (string) $request->request->get('_csrf_token', ''));

        if (!$csrfTokenManager->isTokenValid($token)) {
            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        $rowId = $this->getCollectionRowId($collection);

        $entityManager->remove($collection);
        $entityManager->flush();

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

    private function resolveCategory(Request $request, EntityManagerInterface $entityManager): ?Category
    {
        $newCategoryName = trim((string) $request->request->get('newCategory', ''));

        if ($newCategoryName !== '') {
            $category = (new Category())
                ->setName($newCategoryName)
                ->setSlug($this->createUniqueCategorySlug($newCategoryName, $entityManager))
                ->setIsOnline(false)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setEditedAt(new \DateTimeImmutable());

            $entityManager->persist($category);

            return $category;
        }

        $categoryId = $request->request->getInt('category');
        if ((string) $request->request->get('category') === '__new__') {
            return null;
        }

        if ($categoryId <= 0) {
            return null;
        }

        $category = $entityManager->getRepository(Category::class)->find($categoryId);

        return $category instanceof Category ? $category : null;
    }

    private function createRequestedClothesForCollection(Request $request, EntityManagerInterface $entityManager, Collections $collection): void
    {
        $clothes = $request->request->all('clothes');
        if (!is_array($clothes) || $clothes === []) {
            return;
        }

        foreach ($clothes as $index => $data) {
            if (!is_array($data) || ($data['enabled'] ?? '0') !== '1') {
                continue;
            }

            $uploadedImages = $request->files->all('clotheImages_'.$index);
            $this->createClotheForCollection($data, is_array($uploadedImages) ? $uploadedImages : [], $entityManager, $collection);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param list<UploadedFile> $uploadedImages
     */
    private function createClotheForCollection(array $data, array $uploadedImages, EntityManagerInterface $entityManager, Collections $collection): void
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 70) {
            throw new \InvalidArgumentException('Le nom du vetement est obligatoire et limite a 70 caracteres.');
        }

        $description = trim((string) ($data['description'] ?? ''));
        $metaDescription = trim((string) ($data['metadescription'] ?? ''));
        if (mb_strlen($metaDescription) > 180) {
            throw new \InvalidArgumentException('La meta description est limitee a 180 caracteres.');
        }

        $price = (int) ($data['price'] ?? 0);
        if ($price <= 0) {
            throw new \InvalidArgumentException('Le prix du vetement doit etre superieur a 0.');
        }

        $stock = (int) ($data['stock'] ?? 0);
        if ($stock < 0) {
            throw new \InvalidArgumentException('Le stock du vetement ne peut pas etre negatif.');
        }

        $selectedSizes = $data['sizes'] ?? [];
        $sizes = array_values(array_intersect(ClotheService::AVAILABLE_SIZES, is_array($selectedSizes) ? $selectedSizes : []));
        if ($sizes === []) {
            throw new \InvalidArgumentException('Selectionne au moins une taille pour le vetement.');
        }

        $color = $this->resolveClotheColorFromData($data, $entityManager);
        if (!$color instanceof Clothescolor) {
            throw new \InvalidArgumentException('Selectionne une couleur ou cree une nouvelle couleur.');
        }

        $images = $this->storeClotheImages($uploadedImages, $name);
        if ($images === []) {
            throw new \InvalidArgumentException('Ajoute au moins une image pour le vetement.');
        }

        $slug = $this->createClotheSlug($collection, $color);
        foreach ($sizes as $sizeName) {
            $size = $this->findOrCreateSize($sizeName, $entityManager);
            $clothe = (new Clothes())
                ->setName($name)
                ->setDescription($description !== '' ? $description : null)
                ->setMetadescription($metaDescription !== '' ? $metaDescription : null)
                ->setPrice($price)
                ->setStock($stock)
                ->setImages(array_map(static fn (ClotheImageInput $image): string => $image->path, $images))
                ->setCollection($collection)
                ->setColor($color)
                ->setSize($size)
                ->setSku($this->createSku($slug, $sizeName))
                ->setSlug($slug)
                ->setStatus('draft')
                ->setIsOnline(false)
                ->setIsBestseller(false)
                ->setIsInCarousel(false)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setEditedAt(new \DateTimeImmutable());

            $entityManager->persist($clothe);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveClotheColorFromData(array $data, EntityManagerInterface $entityManager): ?Clothescolor
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

        $colorId = (int) ($data['color'] ?? 0);
        if ($colorId <= 0) {
            return null;
        }

        $color = $entityManager->getRepository(Clothescolor::class)->find($colorId);

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
            if (!$image instanceof UploadedFile || !$this->isValidIllustration($image)) {
                continue;
            }

            $extension = strtolower((string) $image->guessExtension());
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }

            if ($extension === '' || !in_array($extension, self::ILLUSTRATION_EXTENSIONS, true)) {
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

    private function createClotheSlug(Collections $collection, Clothescolor $color): string
    {
        return strtolower((string) (new AsciiSlugger())->slug(sprintf('%s %s', $collection->getName(), $color->getName())));
    }

    private function createSku(string $slug, string $sizeName): string
    {
        return strtoupper(sprintf('%s-%s-%s', $slug, $sizeName, bin2hex(random_bytes(2))));
    }

    private function storeIllustration(Collections $collection, UploadedFile $image): string
    {
        $directory = $this->getParameter('kernel.project_dir').'/public/images/upload/collections/'.$collection->getId();
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create collection upload directory.');
        }

        $extension = strtolower((string) $image->guessExtension());
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        if ($extension === '' || !in_array($extension, self::ILLUSTRATION_EXTENSIONS, true)) {
            $extension = strtolower((string) $image->getClientOriginalExtension());
        }

        $filename = sprintf('collection-%d-%s.%s', $collection->getId(), bin2hex(random_bytes(4)), $extension);
        $image->move($directory, $filename);

        return '/images/upload/collections/'.$collection->getId().'/'.$filename;
    }

    private function isValidIllustration(UploadedFile $image): bool
    {
        $extension = strtolower((string) $image->getClientOriginalExtension());
        $mimeType = (string) $image->getMimeType();

        return in_array($extension, self::ILLUSTRATION_EXTENSIONS, true)
            && in_array($mimeType, self::ILLUSTRATION_MIME_TYPES, true);
    }

    private function createUniqueCategorySlug(string $name, EntityManagerInterface $entityManager): string
    {
        $baseSlug = strtolower((string) (new AsciiSlugger())->slug($name));
        $baseSlug = substr($baseSlug !== '' ? $baseSlug : 'categorie', 0, 60);
        $slug = $baseSlug;
        $index = 1;

        while ($entityManager->getRepository(Category::class)->findOneBy(['slug' => $slug]) instanceof Category) {
            $slug = sprintf('%s-%d', $baseSlug, $index);
            ++$index;
        }

        return $slug;
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
