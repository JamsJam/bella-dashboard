<?php

namespace App\Controller\Avatar;

use App\Provider\PageMetadata\PageMetadataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ColorController extends AbstractController
{
    #[Route('/avatar/color', name: 'app_avatar_color_index')]
    public function index(PageMetadataProvider $pageMetadata, Request $request): Response
    {
        $metaData = $pageMetadata->getPageMetada($request->attributes->get('_route'));

        return $this->render('avatar/color/index.html.twig', [
            'metaData' => $metaData,
        ]);
    }
}
