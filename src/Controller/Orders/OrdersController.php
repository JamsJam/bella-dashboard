<?php

namespace App\Controller\Orders;

use App\Entity\Orders\Orders;
use App\Service\BreadscrumbsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrdersController extends AbstractController
{
    private const COLUMNS = [
        ['key' => 'orderReference', 'label' => 'Numero', 'sortable' => true],
        ['key' => 'total', 'label' => 'Prix', 'sortable' => true],
        ['key' => 'status', 'label' => 'Statut', 'sortable' => true],
        ['key' => 'createdAt', 'label' => 'Date', 'sortable' => true],
    ];

    private const SORTS = [
        'orderReference' => 'o.orderReference',
        'total' => 'o.total',
        'status' => 'o.status',
        'createdAt' => 'o.createdAt',
    ];

    #[Route('/orders', name: 'app_orders', methods: ['GET'])]
    public function index(Request $request, BreadscrumbsService $breadscrumbs, EntityManagerInterface $entityManager): Response
    {
        return $this->render('orders/index.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve((string) $request->attributes->get('_route')),
            'table' => $this->createTableData($request, $entityManager),
        ]);
    }

    #[Route('/orders/table', name: 'app_orders_table', methods: ['GET'])]
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

        $queryBuilder = $entityManager->getRepository(Orders::class)
            ->createQueryBuilder('o')
            ->orderBy(self::SORTS[$sort], $direction);

        if ($search !== '') {
            $queryBuilder
                ->andWhere('LOWER(o.orderReference) LIKE :search OR LOWER(o.status) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($search).'%');
        }

        $orders = $queryBuilder
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();

        return [
            'columns' => self::COLUMNS,
            'rows' => array_map(fn (Orders $order): array => $this->mapOrder($order), $orders),
            'url' => $this->generateUrl('app_orders_table'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ];
    }

    private function mapOrder(Orders $order): array
    {
        return [
            'orderReference' => $order->getOrderReference() ?: sprintf('#%d', $order->getId()),
            'total' => $this->formatPrice((int) $order->getTotal()),
            'status' => $order->getStatus(),
            'createdAt' => $order->getCreatedAt()?->format('d/m/Y H:i') ?? '',
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

    private function formatPrice(int $amount): string
    {
        return number_format($amount / 100, 2, ',', ' ').' EUR';
    }
}
