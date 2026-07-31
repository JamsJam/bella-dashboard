<?php

namespace App\Controller\Reviews;

use App\Entity\Reviews\Review;
use App\Enum\ReviewStatus;
use App\Repository\Reviews\ReviewRepository;
use App\Service\BreadscrumbsService;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReviewsController extends AbstractController
{
    private const PAGE_SIZE = 20;
    private const COLUMNS = [
        ['key' => 'review', 'label' => 'Avis', 'sortable' => true, 'raw' => true],
        ['key' => 'customer', 'label' => 'Client', 'sortable' => true, 'raw' => true],
        ['key' => 'product', 'label' => 'Produit', 'sortable' => true, 'raw' => true],
        ['key' => 'rating', 'label' => 'Note', 'sortable' => true, 'raw' => true],
        ['key' => 'status', 'label' => 'Statut', 'sortable' => true, 'raw' => true],
        ['key' => 'updatedAt', 'label' => 'Dernière activité', 'sortable' => true, 'raw' => true],
        ['key' => 'actions', 'label' => 'Actions', 'sortable' => false, 'raw' => true],
    ];
    private const SORTS = [
        'review' => 'r.id', 'customer' => 'customer.email', 'product' => 'product.name',
        'rating' => 'r.rating', 'status' => 'r.status', 'updatedAt' => 'r.updatedAt',
    ];

    #[Route('/reviews', name: 'app_reviews', methods: ['GET'])]
    public function index(Request $request, ReviewRepository $reviews, BreadscrumbsService $breadcrumbs): Response
    {
        return $this->render('reviews/index.html.twig', [
            'breadscrumbs' => $breadcrumbs->resolve('app_reviews'),
            'table' => $this->createTableData($request, $reviews),
            'summary' => $this->createSummary($reviews),
        ]);
    }

    #[Route('/reviews/table', name: 'app_reviews_table', methods: ['GET'])]
    public function table(Request $request, ReviewRepository $reviews): JsonResponse
    {
        $table = $this->createTableData($request, $reviews);
        return $this->json([
            'html' => $this->renderView('ui/components/data-table/_rows.html.twig', ['columns' => $table['columns'], 'rows' => $table['rows']]),
            'pagination' => $this->renderView('ui/components/data-table/_pagination.html.twig', ['pagination' => $table['pagination']]),
            'page' => $table['pagination']['page'],
        ]);
    }

    #[Route('/reviews/{id}', name: 'app_reviews_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Review $review, Request $request, BreadscrumbsService $breadcrumbs): Response
    {
        return $this->render('reviews/show.html.twig', [
            'breadscrumbs' => $breadcrumbs->resolve(
                (string) $request->attributes->get('_route'),
                currentLabel: 'Avis #'.$review->getId(),
            ),
            'review' => $review,
        ]);
    }

    private function createTableData(Request $request, ReviewRepository $reviews): array
    {
        $search = trim($request->query->getString('search'));
        $sort = array_key_exists($request->query->getString('sort'), self::SORTS) ? $request->query->getString('sort') : 'updatedAt';
        $direction = strtolower($request->query->getString('direction')) === 'asc' ? 'asc' : 'desc';
        $status = ReviewStatus::tryFrom($request->query->getString('status'));
        $requestedPage = max(1, $request->query->getInt('page', 1));

        $queryBuilder = $reviews->createQueryBuilder('r')
            ->addSelect('customer', 'product', 'orders')
            ->join('r.customer', 'customer')->join('r.product', 'product')->join('r.order', 'orders')
            ->orderBy(self::SORTS[$sort], $direction)->addOrderBy('r.id', 'DESC');
        if ($search !== '') {
            $queryBuilder->andWhere('LOWER(customer.email) LIKE :search OR LOWER(product.name) LIKE :search OR LOWER(orders.orderReference) LIKE :search OR LOWER(r.comment) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($search).'%');
        }
        if ($status instanceof ReviewStatus) {
            $queryBuilder->andWhere('r.status = :status')->setParameter('status', $status);
        }

        $count = count(new Paginator(clone $queryBuilder));
        $totalPages = max(1, (int) ceil($count / self::PAGE_SIZE));
        $page = min($requestedPage, $totalPages);
        $items = $queryBuilder->setFirstResult(($page - 1) * self::PAGE_SIZE)->setMaxResults(self::PAGE_SIZE)->getQuery()->getResult();

        return [
            'columns' => self::COLUMNS,
            'rows' => array_map(fn (Review $review): array => $this->mapReview($review), $items),
            'url' => $this->generateUrl('app_reviews_table'), 'sort' => $sort, 'direction' => $direction,
            'search' => $search, 'searchPlaceholder' => 'Client, produit, commande ou commentaire',
            'filters' => [[
                'name' => 'status', 'label' => 'Statut', 'value' => $status?->value ?? '',
                'options' => array_merge([['value' => '', 'label' => 'Tous les avis']], array_map(
                    static fn (ReviewStatus $item): array => ['value' => $item->value, 'label' => $item->label()],
                    ReviewStatus::cases(),
                )),
            ]],
            'pagination' => ['page' => $page, 'totalPages' => $totalPages, 'totalItems' => $count, 'pages' => range(max(1, $page - 2), min($totalPages, $page + 2))],
        ];
    }

    private function mapReview(Review $review): array
    {
        return [
            'review' => $this->renderView('reviews/_review_cell.html.twig', ['review' => $review]),
            'customer' => $this->renderView('reviews/_customer_cell.html.twig', ['review' => $review]),
            'product' => $this->renderView('reviews/_product_cell.html.twig', ['review' => $review]),
            'rating' => $this->renderView('reviews/_rating.html.twig', ['review' => $review]),
            'status' => $this->renderView('reviews/_status_badge.html.twig', ['review' => $review]),
            'updatedAt' => $review->getUpdatedAt()->format('d/m/Y à H:i'),
            'actions' => $this->renderView('reviews/_table_actions.html.twig', ['review' => $review]),
        ];
    }

    /** @return array{total: int, pending: int, average: float} */
    private function createSummary(ReviewRepository $reviews): array
    {
        $average = $reviews->createQueryBuilder('review')
            ->select('COALESCE(AVG(review.rating), 0)')
            ->andWhere('review.status = :status')
            ->setParameter('status', ReviewStatus::Accepted)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $reviews->count([]),
            'pending' => $reviews->count(['status' => ReviewStatus::Pending]),
            'average' => round((float) $average, 1),
        ];
    }
}
