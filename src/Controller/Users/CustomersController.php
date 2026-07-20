<?php

namespace App\Controller\Users;

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
    private const COLUMNS = [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'email', 'label' => 'Email', 'sortable' => true],
        ['key' => 'ordersCount', 'label' => 'Commandes', 'sortable' => true],
        ['key' => 'createdAt', 'label' => 'Date', 'sortable' => true],
        ['key' => 'actions', 'label' => 'Actions', 'sortable' => false, 'raw' => true],
    ];

    private const SORTS = [
        'id' => 'c.id',
        'email' => 'c.email',
        'ordersCount' => 'ordersCount',
        'createdAt' => 'c.createdAt',
    ];

    #[Route('/customers', name: 'app_customers', methods: ['GET'])]
    public function index(Request $request, BreadscrumbsService $breadscrumbs, EntityManagerInterface $entityManager): Response
    {
        return $this->render('customers/index.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve((string) $request->attributes->get('_route')),
            'table' => $this->createTableData($request, $entityManager),
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
        ]);
    }

    #[Route('/customers/{id}', name: 'app_customers_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Customers $customer, Request $request, BreadscrumbsService $breadscrumbs): Response
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

        $results = $queryBuilder
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();

        return [
            'columns' => self::COLUMNS,
            'rows' => array_map(fn (array $result): array => $this->mapCustomer($result), $results),
            'url' => $this->generateUrl('app_customers_table'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ];
    }

    private function mapCustomer(array $result): array
    {
        $customer = $result[0] ?? null;

        if (!$customer instanceof Customers) {
            return [];
        }

        return [
            'id' => (string) $customer->getId(),
            'email' => $customer->getEmail(),
            'ordersCount' => (string) ($result['ordersCount'] ?? 0),
            'createdAt' => $customer->getCreatedAt()?->format('d/m/Y H:i') ?? '',
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
}
