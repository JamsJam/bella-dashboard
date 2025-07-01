<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AboutController extends AbstractController
{
    #[Route('/about', name: 'app_front_about')]
    public function index(): Response
    {
        return $this->render('front/about/index.html.twig', [
            'controller_name' => 'AboutController',
        ]);
    }
}
