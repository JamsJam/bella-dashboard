<?php

namespace App\Controller\Clothes;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CollectionsController extends AbstractController
{
    #[Route('/clothes/collections', name: 'app_clothes_collections')]
    public function index(): Response
    {
        return $this->render('clothes/collections/index.html.twig', [
            'controller_name' => 'CollectionsController',
        ]);
    }
}
