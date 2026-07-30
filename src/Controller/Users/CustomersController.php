<?php

namespace App\Controller\Users;

use App\Application\Config\Provider\OrdersConfigProvider;
use App\Entity\Users\Customers;
use App\Service\BreadscrumbsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CustomersController extends AbstractController
{
    private const PAGE_SIZE = 20;

    private const COLUMNS = [
        ['key' => 'email', 'label' => 'Client', 'sortable' => true, 'raw' => true],
        ['key' => 'confirmed', 'label' => 'Statut', 'sortable' => true, 'raw' => true],
        ['key' => 'ordersCount', 'label' => 'Commandes', 'sortable' => true, 'raw' => true],
        ['key' => 'createdAt', 'label' => 'Inscription', 'sortable' => true, 'raw' => true],
        ['key' => 'actions', 'label' => 'Actions', 'sortable' => false, 'raw' => true],
    ];

    private const SORTS = [
        'email' => 'c.email',
        'confirmed' => 'c.isSignupConfirmed',
        'ordersCount' => 'ordersCount',
        'createdAt' => 'c.createdAt',
    ];

    #[Route('/customers', name: 'app_customers', methods: ['GET'])]
    public function index(Request $request, BreadscrumbsService $breadscrumbs, EntityManagerInterface $entityManager): Response
    {
        return $this->render('customers/index.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve((string) $request->attributes->get('_route')),
            'table' => $this->createTableData($request, $entityManager),
            'summary' => $this->createSummary($entityManager),
        ]);
    }

    #[Route('/customers/table', name: 'app_customers_table', methods: ['GET'])]
    public function table(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $table = $this->createTableData($request, $entityManager);

        return $this->json([
            'html' => $this->renderView('ui/components/data-table/_rows.html.twig', [
                'columns' => $table['columns'],
                'rows' => $table['rows'],
            ]),
            'pagination' => $this->renderView('ui/components/data-table/_pagination.html.twig', [
                'pagination' => $table['pagination'],
            ]),
            'page' => $table['pagination']['page'],
        ]);
    }

    #[Route('/customers/{id}', name: 'app_customers_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(
        Customers $customer,
        Request $request,
        BreadscrumbsService $breadscrumbs,
        OrdersConfigProvider $ordersConfigProvider,
    ): Response
    {
        $orders = $customer->getOrders()->toArray();
        usort(
            $orders,
            static fn ($left, $right): int => ($right->getCreatedAt()?->getTimestamp() ?? 0) <=> ($left->getCreatedAt()?->getTimestamp() ?? 0),
        );

        return $this->render('customers/show.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve(
                (string) $request->attributes->get('_route'),
                currentLabel: $customer->getEmail() ?? sprintf('Client #%d', $customer->getId()),
            ),
            'customer' => $customer,
            'orders' => $orders,
            'vatRate' => $ordersConfigProvider->get()->vat,
        ]);
    }

    #[Route('/customers/{id}/update', name: 'app_customers_update', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function update(Customers $customer, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('customer_update_'.$customer->getId(), (string) $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $email = mb_strtolower(trim((string) $request->request->get('email')));
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->addFlash('error', 'L’adresse e-mail est invalide.');

            return $this->redirectToRoute('app_customers_show', ['id' => $customer->getId()]);
        }

        $existingCustomer = $entityManager->getRepository(Customers::class)->findOneBy(['email' => $email]);
        if ($existingCustomer instanceof Customers && $existingCustomer->getId() !== $customer->getId()) {
            $this->addFlash('error', 'Cette adresse e-mail est déjà utilisée.');

            return $this->redirectToRoute('app_customers_show', ['id' => $customer->getId()]);
        }

        $customer
            ->setEmail($email)
            ->setIsSignupConfirmed($request->request->getBoolean('isSignupConfirmed'))
            ->setEditedAt(new \DateTimeImmutable());

        if ($customer->isSignupConfirmed()) {
            $customer
                ->setSignupVerificationCode(null)
                ->setSignupVerificationExpiresAt(null);
        }

        $entityManager->flush();
        $this->addFlash('success', 'Le client a été mis à jour.');

        return $this->redirectToRoute('app_customers_show', ['id' => $customer->getId()]);
    }

    #[Route('/customers/{id}/delete', name: 'app_customers_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Customers $customer, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('customer_delete_'.$customer->getId(), (string) $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if (!$customer->getOrders()->isEmpty()) {
            $this->addFlash('error', 'Ce client ne peut pas être supprimé car il possède des commandes.');

            return $this->redirectToRoute('app_customers_show', ['id' => $customer->getId()]);
        }

        $entityManager->remove($customer);
        $entityManager->flush();
        $this->addFlash('success', 'Le client a été supprimé.');

        return $this->redirectToRoute('app_customers');
    }

    private function createTableData(Request $request, EntityManagerInterface $entityManager): array
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = $this->normalizeSort((string) $request->query->get('sort', 'createdAt'));
        $direction = $this->normalizeDirection((string) $request->query->get('direction', 'desc'));
        $confirmation = $this->normalizeConfirmation((string) $request->query->get('confirmation', ''));
        $requestedPage = max(1, $request->query->getInt('page', 1));

        $queryBuilder = $entityManager->getRepository(Customers::class)
            ->createQueryBuilder('c')
            ->select('c, COUNT(o.id) AS ordersCount')
            ->leftJoin('c.orders', 'o')
            ->groupBy('c.id')
            ->orderBy(self::SORTS[$sort], $direction);

        if ($search !== '') {
            $queryBuilder
                ->andWhere('LOWER(c.email) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($search).'%');
        }

        if ($confirmation !== null) {
            $queryBuilder
                ->andWhere('c.isSignupConfirmed = :confirmed')
                ->setParameter('confirmed', $confirmation === 'confirmed');
        }

        $countQueryBuilder = clone $queryBuilder;
        $totalItems = (int) $countQueryBuilder
            ->resetDQLPart('orderBy')
            ->resetDQLPart('groupBy')
            ->select('COUNT(DISTINCT c.id)')
            ->getQuery()
            ->getSingleScalarResult();
        $totalPages = max(1, (int) ceil($totalItems / self::PAGE_SIZE));
        $page = min($requestedPage, $totalPages);

        $results = $queryBuilder
            ->setFirstResult(($page - 1) * self::PAGE_SIZE)
            ->setMaxResults(self::PAGE_SIZE)
            ->getQuery()
            ->getResult();

        return [
            'columns' => self::COLUMNS,
            'rows' => array_map(fn (array $result): array => $this->mapCustomer($result), $results),
            'url' => $this->generateUrl('app_customers_table'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
            'searchPlaceholder' => 'Rechercher par adresse e-mail',
            'filters' => [[
                'name' => 'confirmation',
                'label' => 'Confirmation du compte',
                'value' => $confirmation ?? '',
                'options' => [
                    ['value' => '', 'label' => 'Tous les comptes'],
                    ['value' => 'confirmed', 'label' => 'Comptes confirmés'],
                    ['value' => 'pending', 'label' => 'En attente de confirmation'],
                ],
            ]],
            'pagination' => [
                'page' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalItems,
                'pages' => range(max(1, $page - 2), min($totalPages, $page + 2)),
            ],
        ];
    }

    private function mapCustomer(array $result): array
    {
        $customer = $result[0] ?? null;

        if (!$customer instanceof Customers) {
            return [];
        }

        return [
            'email' => $this->renderView('customers/_customer_cell.html.twig', ['customer' => $customer]),
            'confirmed' => $this->renderView('customers/_status_badge.html.twig', ['customer' => $customer]),
            'ordersCount' => $this->renderView('customers/_orders_count.html.twig', [
                'count' => (int) ($result['ordersCount'] ?? 0),
            ]),
            'createdAt' => $this->renderView('customers/_date_cell.html.twig', ['customer' => $customer]),
            'actions' => $this->renderView('customers/_table_actions.html.twig', [
                'customer' => $customer,
            ]),
        ];
    }

    private function normalizeSort(string $sort): string
    {
        return array_key_exists($sort, self::SORTS) ? $sort : 'createdAt';
    }

    private function normalizeDirection(string $direction): string
    {
        return strtolower($direction) === 'asc' ? 'asc' : 'desc';
    }

    private function normalizeConfirmation(string $confirmation): ?string
    {
        return in_array($confirmation, ['confirmed', 'pending'], true) ? $confirmation : null;
    }

    /** @return array{total: int, confirmed: int, pending: int} */
    private function createSummary(EntityManagerInterface $entityManager): array
    {
        $repository = $entityManager->getRepository(Customers::class);
        $total = $repository->count([]);
        $confirmed = $repository->count(['isSignupConfirmed' => true]);

        return [
            'total' => $total,
            'confirmed' => $confirmed,
            'pending' => $total - $confirmed,
        ];
    }
}
