<?php

namespace App\Controller;

use App\Service\BreadscrumbsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(Request $request, BreadscrumbsService $breadscrumbs): Response
    {
        $route = $request->attributes->get('_route');
        return $this->render('home/index.html.twig', [
            "breadscrumbs" => $breadscrumbs->resolve($route),
            'controller_name' => 'HomeController',
        ]);
    }
}
