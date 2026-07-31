<?php

namespace App\Controller;

use App\Repository\Clothes\ClothesRepository;
use App\Repository\Clothes\ClothesVariantRepository;
use App\Repository\Orders\OrdersRepository;
use App\Repository\Users\CustomersRepository;
use App\Service\BreadscrumbsService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        Request $request,
        BreadscrumbsService $breadscrumbs,
        OrdersRepository $ordersRepository,
        CustomersRepository $customersRepository,
        ClothesRepository $clothesRepository,
        ClothesVariantRepository $variantsRepository,
        Connection $connection,
    ): Response {
        $route = $request->attributes->get('_route');
        return $this->render('home/index.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve($route),
            'summary' => $ordersRepository->getDashboardSummary(new \DateTimeImmutable('first day of this month 00:00:00')),
            'customersCount' => $customersRepository->count([]),
            'productsCount' => $clothesRepository->count([]),
            'onlineProductsCount' => $clothesRepository->count(['isOnline' => true]),
            'lowStockCount' => $variantsRepository->countLowStock(),
            'latestOrders' => $ordersRepository->findLatest(),
            'reviewsSummary' => $this->reviewsSummary($connection),
        ]);
    }

    /** @return array{average: float, accepted: int} */
    private function reviewsSummary(Connection $connection): array
    {
        $row = $connection->createQueryBuilder()
            ->select('COALESCE(AVG(CASE WHEN status = :accepted THEN rating END), 0) AS average')
            ->addSelect('SUM(CASE WHEN status = :accepted THEN 1 ELSE 0 END) AS accepted')
            ->from('review')
            ->setParameter('accepted', 'accepted')
            ->executeQuery()
            ->fetchAssociative();

        return [
            'average' => round((float) ($row['average'] ?? 0), 1),
            'accepted' => (int) ($row['accepted'] ?? 0),
        ];
    }
}
