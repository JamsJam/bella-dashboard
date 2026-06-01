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
