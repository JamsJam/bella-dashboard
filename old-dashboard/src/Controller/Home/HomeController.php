<?php

namespace App\Controller\Home;

use App\Service\ThemeService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Provider\PageMetadata\PageMetadataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        PageMetadataProvider $pageMetadata, 
        Request $request,
        ThemeService $theme_service,
    ): Response
    {
        $theme = $theme_service->getTheme();
        // $currentRoute = $request->attributes->get('_route');
        // $metaData = $pageMetadata->getPageMetada($request->attributes->get('_route'));

        return $this->render('home/index.html.twig', [
            // 'metaData' => $metaData,
            'theme' => $theme,
        ]);
    }
}
