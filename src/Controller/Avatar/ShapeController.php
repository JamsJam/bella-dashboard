<?php

namespace App\Controller\Avatar;

use App\Provider\PageMetadata\PageMetadataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShapeController extends AbstractController
{
    #[Route('/avatar/shape', name: 'app_avatar_shape_index')]
    public function index(PageMetadataProvider $pageMetadata, Request $request): Response
    {
        $metaData = $pageMetadata->getPageMetada($request->attributes->get('_route'));

        return $this->render('avatar/shape/index.html.twig', [
            'metaData' => $metaData,
        ]);
    }
}
