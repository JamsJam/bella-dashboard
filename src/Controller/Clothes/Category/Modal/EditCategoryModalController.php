<?php

namespace App\Controller\Clothes\Category\Modal;

use App\Entity\Category\Category;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EditCategoryModalController extends AbstractController
{
    #[Route(
        '/clothes/categories/{id}/edit/modal',
        name: 'app_clothe_category_edit_modal',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
    )]
    public function show(Category $category): Response
    {
        return $this->render(
            'clothes/categories/turbo/edit.stream.html.twig',
            ['category' => $category],
            new Response(headers: ['Content-Type' => 'text/vnd.turbo-stream.html']),
        );
    }
}
