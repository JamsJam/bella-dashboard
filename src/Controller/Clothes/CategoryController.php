<?php

namespace App\Controller\Clothes;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Provider\PageMetadata\PageMetadataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CategoryController extends AbstractController
{
    #[Route('/clothes/category', name: 'app_clothes_category')]
    public function index(
        PageMetadataProvider $pageMetadata, 
        Request $request
    ): Response
    {
        $metaData = $pageMetadata->getPageMetada($request->attributes->get('_route'));

        return $this->render('clothes/category/index.html.twig', [
            'metaData' => $metaData,
        ]);
    }
}
