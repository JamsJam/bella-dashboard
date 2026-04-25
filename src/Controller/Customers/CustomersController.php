<?php

namespace App\Controller\Customers;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CustomersController extends AbstractController
{
    #[Route('/clients/', name: 'app_customers')]
    public function index(): Response
    {
        return $this->render('customers/index.html.twig', [
            'theme' => 'light',
            'controller_name' => 'CustomersController',
        ]);
    }
}
