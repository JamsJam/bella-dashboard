<?php

namespace App\Controller\Home;

use App\Provider\PageMetadata\PageMetadataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(PageMetadataProvider $pageMetadata, Request $request): Response
    {
        // $currentRoute = $request->attributes->get('_route');
        $metaData = $pageMetadata->getPageMetada($request->attributes->get('_route'));

        return $this->render('home/index.html.twig', [
            'metaData' => $metaData,
        ]);
    }
}
