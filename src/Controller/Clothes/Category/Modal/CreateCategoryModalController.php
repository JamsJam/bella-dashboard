<?php

namespace App\Controller\Clothes\Category\Modal;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CreateCategoryModalController extends AbstractController
{
    #[Route('/clothes/categories/create/modal', name: 'app_clothes_categories_create_modal', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render(
            'clothes/categories/turbo/create.stream.html.twig',
            response: new Response(headers: ['Content-Type' => 'text/vnd.turbo-stream.html']),
        );
    }
}
