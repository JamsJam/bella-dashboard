<?php

namespace App\Controller\Clothes;

use App\Entity\Category\Category;
use App\Application\Clothes\Guard\Category\CategoryOnlineGuard;
use App\Application\Clothes\Guard\Collection\CollectionOnlineGuard;
use App\Entity\Collections\Collections;
use App\Application\Clothes\Services\CategoryPublicationService;
use App\Notifier\Services\FlashService;
use App\Service\BreadscrumbsService;
use App\Service\LoggerService;
use App\UI\Toggle\ToggleActionModel;
use App\UI\Toggle\ToggleModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class CategoryController extends AbstractController
{
    private const ILLUSTRATION_EXTENSIONS = ['png', 'jpg', 'jpeg', 'svg'];
    private const ILLUSTRATION_MIME_TYPES = ['image/png', 'image/jpeg', 'image/svg+xml'];

    private const COLUMNS = [
        ['key' => 'name', 'label' => 'Nom', 'sortable' => true],
        ['key' => 'isOnline', 'label' => 'En ligne', 'sortable' => true, 'raw' => true],
        ['key' => 'collectionsCount', 'label' => 'Collections', 'sortable' => true],
        ['key' => 'actions', 'label' => 'Actions', 'sortable' => false, 'raw' => true],
    ];

    private const SORTS = [
        'id' => 'c.id',
        'name' => 'c.name',
        'slug' => 'c.slug',
        'isOnline' => 'c.isOnline',
        'collectionsCount' => 'collectionsCount',
        'createdAt' => 'c.createdAt',
    ];

    #[Route('/clothes/categories', name: 'app_clothes_categories', methods: ['GET'])]
    public function index(
        Request $request,
        BreadscrumbsService $breadscrumbs,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response
    {
        return $this->render('clothes/categories/index.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve((string) $request->attributes->get('_route')),
            'tabs' => $this->createTabs(),
            'table' => $this->createTableData($request, $entityManager, $csrfTokenManager),
        ]);
    }

    #[Route('/clothes/categories/table', name: 'app_clothes_categories_table', methods: ['GET'])]
    public function table(Request $request, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrfTokenManager): JsonResponse
    {
        $table = $this->createTableData($request, $entityManager, $csrfTokenManager);

        return $this->json([
            'html' => $this->renderView('ui/components/data-table/_rows.html.twig', [
                'columns' => $table['columns'],
                'rows' => $table['rows'],
            ]),
        ]);
    }

    #[Route('/clothes/categories/create/modal', name: 'app_clothes_categories_create_modal', methods: ['GET'])]
    public function createModal(CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $html = $this->renderView('clothes/categories/_create_modal.html.twig', [
            'action' => $this->generateUrl('app_clothes_categories_create'),
            'csrfToken' => $csrfTokenManager->getToken('category_create')->getValue(),
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    #[Route('/clothes/categories', name: 'app_clothes_categories_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        FlashService $flashService,
        LoggerService $logger,
    ): Response {
        $token = new CsrfToken('category_create', (string) $request->request->get('_csrf_token', ''));

        if (!$csrfTokenManager->isTokenValid($token)) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for category creation.');

            return $this->redirectToRoute('app_clothes_categories');
        }

        $name = trim((string) $request->request->get('name', ''));
        if ($name === '') {
            $flashService->error('Le nom de la categorie est obligatoire.');

            return $this->redirectToRoute('app_clothes_categories');
        }

        $image = $request->files->get('illustration');
        if ($image !== null && (!$image instanceof UploadedFile || !$this->isValidIllustration($image))) {
            $flashService->error('Image invalide. Formats acceptes : PNG, JPEG, SVG.');

            return $this->redirectToRoute('app_clothes_categories');
        }

        $category = (new Category())
            ->setName($name)
            ->setSlug($this->createUniqueSlug($name, $entityManager))
            ->setMetaDescription(trim((string) $request->request->get('metaDescription', '')) ?: null)
            ->setIsOnline(false)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setEditedAt(new \DateTimeImmutable());

        $entityManager->persist($category);
        $entityManager->flush();

        if ($image instanceof UploadedFile) {
            $category->setImage($this->storeIllustration($category, $image));
            $entityManager->flush();
        }

        $flashService->success('Categorie creee hors-ligne.');
        $logger->info('Category created.', [
            'category_id' => $category->getId(),
            'category_name' => $category->getName(),
        ]);

        return $this->redirectToRoute('app_clothe_category_show', ['id' => $category->getId()]);
    }

    #[Route('/clothes/categories/{id}/online/{state}', name: 'app_clothes_categories_toggle_online', requirements: ['state' => 'on|off'], methods: ['POST'])]
    public function toggleOnline(
        Category $category,
        string $state,
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
        CategoryPublicationService $categoryPublicationService,
        CollectionOnlineGuard $collectionOnlineGuard,
        LoggerService $logger,
    ): JsonResponse {
        $tokenId = $this->getOnlineCsrfTokenId($category);
        $token = new CsrfToken($tokenId, (string) $request->headers->get('X-CSRF-TOKEN', ''));

        if (!$csrfTokenManager->isTokenValid($token)) {
            $logger->warning('Invalid CSRF token for category online toggle.', [
                'category_id' => $category->getId(),
                'state' => $state,
            ]);

            return $this->json(['success' => false, 'error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        if ($state === 'on' && !$categoryPublicationService->publish($category)) {
            $logger->warning('Category publication rejected.', [
                'category_id' => $category->getId(),
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Category cannot be published.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($state === 'off') {
            $categoryPublicationService->unpublish($category);
        }

        return $this->json([
            'success' => true,
            'isOnline' => $category->isOnline(),
            'collectionsHtml' => $this->renderView('clothes/categories/_collections_list.html.twig', [
                'category' => $category,
                'collectionPublicationStates' => $this->getCollectionPublicationStates($category, $collectionOnlineGuard),
            ]),
        ]);
    }

    #[Route('/clothes/categories/{id}', name: 'app_clothe_category_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        Category $category,
        CsrfTokenManagerInterface $csrfTokenManager,
        CategoryOnlineGuard $categoryOnlineGuard,
        CollectionOnlineGuard $collectionOnlineGuard,
    ): Response {
        $publicationValidation = $categoryOnlineGuard->canPublish($category);

        return $this->render('clothes/categories/show.html.twig', [
            'breadscrumbs' => [
                ['label' => 'Dashboard', 'route' => 'app_dashboard'],
                ['label' => 'Vêtements', 'route' => 'app_clothes'],
                ['label' => 'Catégories', 'route' => 'app_clothes_categories'],
                ['label' => (string) $category->getName()],
            ],
            'tabs' => $this->createShowTabs($category),
            'category' => $category,
            'onlineToggle' => $this->renderCategoryOnlineToggle($category, $csrfTokenManager),
            'imageUploadAction' => $this->generateUrl('app_clothe_category_image_update', ['id' => $category->getId()]),
            'imageUploadToken' => $csrfTokenManager->getToken($this->getImageCsrfTokenId($category))->getValue(),
            'publicationRequirements' => $publicationValidation->getChecks(),
            'canPublish' => $publicationValidation->canPublish(),
            'collectionPublicationStates' => $this->getCollectionPublicationStates($category, $collectionOnlineGuard),
        ]);
    }

    #[Route('/clothes/categories/{id}/edit/modal', name: 'app_clothe_category_edit_modal', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function editModal(Category $category, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $html = $this->renderView('clothes/categories/_edit_modal.html.twig', [
            'category' => $category,
            'action' => $this->generateUrl('app_clothe_category_update', ['id' => $category->getId()]),
            'csrfToken' => $csrfTokenManager->getToken($this->getEditCsrfTokenId($category))->getValue(),
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    #[Route('/clothes/categories/{id}', name: 'app_clothe_category_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(
        Category $category,
        Request $request,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        FlashService $flashService,
        LoggerService $logger,
    ): Response {
        $token = new CsrfToken($this->getEditCsrfTokenId($category), (string) $request->request->get('_csrf_token', ''));

        if (!$csrfTokenManager->isTokenValid($token)) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for category update.', [
                'category_id' => $category->getId(),
            ]);

            return $this->redirectToRoute('app_clothe_category_show', ['id' => $category->getId()]);
        }

        $name = trim((string) $request->request->get('name', ''));
        if ($name === '') {
            $flashService->error('Le nom de la categorie est obligatoire.');

            return $this->redirectToRoute('app_clothe_category_show', ['id' => $category->getId()]);
        }

        $image = $request->files->get('illustration');
        if ($image !== null && (!$image instanceof UploadedFile || !$this->isValidIllustration($image))) {
            $flashService->error('Image invalide. Formats acceptes : PNG, JPEG, SVG.');

            return $this->redirectToRoute('app_clothe_category_show', ['id' => $category->getId()]);
        }

        $category
            ->setName($name)
            ->setSlug($this->createUniqueSlug($name, $entityManager, $category))
            ->setMetaDescription(trim((string) $request->request->get('metaDescription', '')) ?: null)
            ->setEditedAt(new \DateTimeImmutable());

        if ($image instanceof UploadedFile) {
            try {
                $category->setImage($this->storeIllustration($category, $image));
            } catch (\RuntimeException $exception) {
                $flashService->error('Impossible de creer le dossier d upload.');
                $logger->exception($exception, 'Unable to store category illustration.', [
                    'category_id' => $category->getId(),
                ]);

                return $this->redirectToRoute('app_clothe_category_show', ['id' => $category->getId()]);
            }
        }

        $entityManager->flush();
        $flashService->success('Categorie modifiee.');
        $logger->info('Category updated.', [
            'category_id' => $category->getId(),
        ]);

        return $this->redirectToRoute('app_clothe_category_show', ['id' => $category->getId()]);
    }

    #[Route('/clothes/categories/{id}/delete', name: 'app_clothe_category_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Category $category,
        Request $request,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        LoggerService $logger,
    ): Response {
        $token = new CsrfToken($this->getDeleteCsrfTokenId($category), (string) $request->request->get('_csrf_token', ''));

        if (!$csrfTokenManager->isTokenValid($token)) {
            $logger->warning('Invalid CSRF token for category deletion.', [
                'category_id' => $category->getId(),
            ]);

            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        $rowId = $this->getCategoryRowId($category);

        $entityManager->remove($category);
        $entityManager->flush();
        $logger->info('Category deleted.', [
            'category_id' => $category->getId(),
            'row_id' => $rowId,
        ]);

        return new Response(
            sprintf('<turbo-stream action="remove" target="%s"></turbo-stream>', $rowId),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    #[Route('/clothes/categories/{id}/image', name: 'app_clothe_category_image_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateImage(
        Category $category,
        Request $request,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        FlashService $flashService,
        LoggerService $logger,
    ): Response {
        $token = new CsrfToken($this->getImageCsrfTokenId($category), (string) $request->request->get('_csrf_token', ''));

        if (!$csrfTokenManager->isTokenValid($token)) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for category image update.', [
                'category_id' => $category->getId(),
            ]);

            return $this->redirectToRoute('app_clothe_category_show', ['id' => $category->getId()]);
        }

        $image = $request->files->get('illustration');

        if (!$image instanceof UploadedFile) {
            $flashService->error('Aucune image selectionnee.');

            return $this->redirectToRoute('app_clothe_category_show', ['id' => $category->getId()]);
        }

        if (!$this->isValidIllustration($image)) {
            $flashService->error('Image invalide. Formats acceptes : PNG, JPEG, SVG.');

            return $this->redirectToRoute('app_clothe_category_show', ['id' => $category->getId()]);
        }

        try {
            $category->setImage($this->storeIllustration($category, $image));
        } catch (\RuntimeException $exception) {
            $flashService->error('Impossible de creer le dossier d upload.');
            $logger->exception($exception, 'Unable to store category image.', [
                'category_id' => $category->getId(),
            ]);

            return $this->redirectToRoute('app_clothe_category_show', ['id' => $category->getId()]);
        }

        $category->setEditedAt(new \DateTimeImmutable());
        $entityManager->flush();

        $flashService->success('Image d illustration mise a jour.');

        return $this->redirectToRoute('app_clothe_category_show', ['id' => $category->getId()]);
    }

    private function storeIllustration(Category $category, UploadedFile $image): string
    {
        $directory = $this->getParameter('kernel.project_dir').'/public/images/upload/categories/'.$category->getId();
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create category upload directory.');
        }

        $extension = strtolower((string) $image->guessExtension());
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        if ($extension === '' || !in_array($extension, self::ILLUSTRATION_EXTENSIONS, true)) {
            $extension = strtolower((string) $image->getClientOriginalExtension());
        }

        $filename = sprintf('category-%d-%s.%s', $category->getId(), bin2hex(random_bytes(4)), $extension);
        $image->move($directory, $filename);

        return '/images/upload/categories/'.$category->getId().'/'.$filename;
    }

    private function createTableData(Request $request, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrfTokenManager): array
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = $this->normalizeSort((string) $request->query->get('sort', 'name'));
        $direction = $this->normalizeDirection((string) $request->query->get('direction', 'asc'));

        $queryBuilder = $entityManager->getRepository(Category::class)
            ->createQueryBuilder('c')
            ->select('c, COUNT(col.id) AS collectionsCount')
            ->leftJoin('c.collections', 'col')
            ->groupBy('c.id')
            ->orderBy(self::SORTS[$sort], $direction);

        if ($search !== '') {
            $queryBuilder
                ->andWhere('LOWER(c.name) LIKE :search OR LOWER(c.slug) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($search).'%');
        }

        $results = $queryBuilder
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();

        return [
            'columns' => self::COLUMNS,
            'rows' => array_map(fn (array $result): array => $this->mapCategory($result, $csrfTokenManager), $results),
            'url' => $this->generateUrl('app_clothes_categories_table'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ];
    }

    private function createTabs(): array
    {
        return [
            [
                'id' => 'create',
                'label' => 'Creer une categorie',
                'href' => $this->generateUrl('app_clothes_categories_create_modal'),
                'isActive' => false,
                'attr' => [
                    'data-turbo-stream' => 'true',
                ],
            ],
            [
                'id' => 'schedule-online',
                'label' => 'Programmer une mise en ligne',
                'href' => '#',
                'isActive' => false,
            ],
        ];
    }

    private function createShowTabs(Category $category): array
    {
        return [
            [
                'id' => 'back',
                'label' => 'Retour',
                'href' => $this->generateUrl('app_clothes_categories'),
                'isActive' => false,
            ],
            [
                'id' => 'edit',
                'label' => 'Modifier',
                'href' => $this->generateUrl('app_clothe_category_edit_modal', ['id' => $category->getId()]),
                'isActive' => false,
                'attr' => [
                    'data-turbo-stream' => 'true',
                ],
            ],
            [
                'id' => 'delete',
                'label' => 'Supprimer',
                'href' => '#',
                'isActive' => false,
            ],
        ];
    }

    private function mapCategory(array $result, CsrfTokenManagerInterface $csrfTokenManager): array
    {
        $category = $result[0] ?? null;

        if (!$category instanceof Category) {
            return [];
        }

        return [
            '_rowId' => $this->getCategoryRowId($category),
            'id' => (string) $category->getId(),
            'name' => (string) $category->getName(),
            'isOnline' => $this->renderCategoryOnlineToggle($category, $csrfTokenManager),
            'collectionsCount' => (string) ($result['collectionsCount'] ?? 0),
            'actions' => $this->renderCategoryActions($category, $csrfTokenManager),
        ];
    }

    private function renderCategoryActions(Category $category, CsrfTokenManagerInterface $csrfTokenManager): string
    {
        return $this->renderView('clothes/categories/_actions.html.twig', [
            'categoryId' => $category->getId(),
            'showUrl' => $this->generateUrl('app_clothe_category_show', ['id' => $category->getId()]),
            'deleteUrl' => $this->generateUrl('app_clothe_category_delete', ['id' => $category->getId()]),
            'deleteToken' => $csrfTokenManager->getToken($this->getDeleteCsrfTokenId($category))->getValue(),
        ]);
    }

    private function renderCategoryOnlineToggle(Category $category, CsrfTokenManagerInterface $csrfTokenManager): string
    {
        $csrfToken = $csrfTokenManager->getToken($this->getOnlineCsrfTokenId($category))->getValue();
        $onlineAction = new ToggleActionModel(
            url: $this->generateUrl('app_clothes_categories_toggle_online', [
                'id' => $category->getId(),
                'state' => 'on',
            ]),
            csrfToken: $csrfToken,
            label: 'En ligne',
        );
        $offlineAction = new ToggleActionModel(
            url: $this->generateUrl('app_clothes_categories_toggle_online', [
                'id' => $category->getId(),
                'state' => 'off',
            ]),
            csrfToken: $csrfToken,
            label: 'Hors ligne',
        );

        $toggle = new ToggleModel(
            id: 'category-online-'.$category->getId(),
            label: $category->isOnline() ? 'En ligne' : 'Hors ligne',
            checked: (bool) $category->isOnline(),
            name: 'category_online_'.$category->getId(),
            payload: [
                'on' => $onlineAction->toArray(),
                'off' => $offlineAction->toArray(),
            ],
        );

        return $this->renderView('ui/components/toggle/_toggle.html.twig', [
            'toggle' => $toggle->toArray(),
        ]);
    }

    private function isValidIllustration(UploadedFile $image): bool
    {
        $extension = strtolower((string) $image->getClientOriginalExtension());
        $mimeType = (string) $image->getMimeType();

        return in_array($extension, self::ILLUSTRATION_EXTENSIONS, true)
            && in_array($mimeType, self::ILLUSTRATION_MIME_TYPES, true);
    }

    private function createUniqueSlug(string $name, EntityManagerInterface $entityManager, ?Category $currentCategory = null): string
    {
        $baseSlug = strtolower((string) (new AsciiSlugger())->slug($name));
        $baseSlug = substr($baseSlug !== '' ? $baseSlug : 'categorie', 0, 60);
        $slug = $baseSlug;
        $index = 1;

        while ($this->slugExists($slug, $entityManager, $currentCategory)) {
            $slug = sprintf('%s-%d', $baseSlug, $index);
            ++$index;
        }

        return $slug;
    }

    private function slugExists(string $slug, EntityManagerInterface $entityManager, ?Category $currentCategory = null): bool
    {
        $category = $entityManager->getRepository(Category::class)->findOneBy(['slug' => $slug]);

        return $category instanceof Category && $category->getId() !== $currentCategory?->getId();
    }

    private function normalizeSort(string $sort): string
    {
        return array_key_exists($sort, self::SORTS) ? $sort : 'name';
    }

    private function normalizeDirection(string $direction): string
    {
        return strtolower($direction) === 'desc' ? 'desc' : 'asc';
    }

    private function getOnlineCsrfTokenId(Category $category): string
    {
        return 'category_online_'.((string) $category->getId());
    }

    private function getImageCsrfTokenId(Category $category): string
    {
        return 'category_image_'.((string) $category->getId());
    }

    private function getEditCsrfTokenId(Category $category): string
    {
        return 'category_edit_'.((string) $category->getId());
    }

    private function getDeleteCsrfTokenId(Category $category): string
    {
        return 'category_delete_'.((string) $category->getId());
    }

    private function getCategoryRowId(Category $category): string
    {
        return 'category-row-'.((string) $category->getId());
    }

    /**
     * @return array<int, array{canPublish: bool, errors: list<string>}>
     */
    private function getCollectionPublicationStates(Category $category, CollectionOnlineGuard $guard): array
    {
        $states = [];

        foreach ($category->getCollections() as $collection) {
            if (!$collection instanceof Collections || $collection->isOnline()) {
                continue;
            }

            $validation = $guard->canPublish($collection);
            $states[(int) $collection->getId()] = [
                'canPublish' => $validation->canPublish(),
                'errors' => $validation->getErrors(),
            ];
        }

        return $states;
    }
}
