<?php

namespace App\Controller;

use App\Repository\Clothes\ClothesRepository;
use App\Repository\Clothes\ClothesVariantRepository;
use App\Repository\Orders\OrdersRepository;
use App\Repository\Users\CustomersRepository;
use App\Service\BreadscrumbsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        ]);
    }
}
