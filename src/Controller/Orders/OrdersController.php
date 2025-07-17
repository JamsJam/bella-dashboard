<?php

namespace App\Controller\Orders;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrdersController extends AbstractController
{
    #[Route('/orders/orders', name: 'app_orders')]
    public function index(): Response
    {
        return $this->render('orders/index.html.twig', [
            'controller_name' => 'Orders/ordersController',
        ]);
    }
}
