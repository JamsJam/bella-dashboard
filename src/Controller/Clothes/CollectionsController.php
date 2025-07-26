<?php

namespace App\Controller\Clothes;

use App\Entity\collections\collections;
use Symfony\UX\Turbo\TurboBundle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Provider\PageMetadata\PageMetadataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CollectionsController extends AbstractController
{
    #[Route('/clothes/collections', name: 'app_clothes_collections')]
    public function index(
        PageMetadataProvider $pageMetadata, 
        Request $request,
        EntityManagerInterface $entityManagerInterface,
    ): Response
    {
        $metaData = $pageMetadata->getPageMetada($request->attributes->get('_route'));
        $collections = $entityManagerInterface->getRepository(Collections::class)->findAll();

        $block = $this->renderBlockView('clothes/collections/turbo/index.html.twig','index',[
            "collections" => $collections,
            "routePath" => $metaData->getBreadcrumb()[count($metaData->getBreadcrumb()) - 2]->getRoute()
        ]);

                // $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
        return $this->render('clothes/collections/index.html.twig', [
            'metaData' => $metaData,
            'block' => $block,
        ]);
    }
};


