<?php

namespace App\Controller\Clothes;

use App\Entity\Clothes\Clothes;
use App\Entity\Category\Category;
use App\Entity\Clothes\Clothescolor;
use Symfony\UX\Turbo\TurboBundle;
use App\Entity\Collections\Collections;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Provider\PageMetadata\PageMetadataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ClothesController extends AbstractController
{
    #[Route('/clothes', name: 'app_clothes')]
    public function index(
        PageMetadataProvider $pageMetadata,
        Request $request,
        EntityManagerInterface $entityManagerInterface,
    ): Response
    {
        $metaData = $pageMetadata->getPageMetada($request->attributes->get('_route'));

        //todo creer une commande permettant d'afficher les vetement classé par groupe
        $clothes = $entityManagerInterface->getRepository(Clothes::class)->findAll();
        $filters = [
            "Categories" => $entityManagerInterface->getRepository(Category::class)->findAll(),
            "Collections" => $entityManagerInterface->getRepository(Collections::class)->findAll(),
            "Couleurs" => $entityManagerInterface->getRepository(Clothescolor::class)->findAll()
        ] ;


        $block = $this->renderBlockView('clothes/clothes/turbo/index.html.twig','index',[
            "clothes" => $clothes,
            "filters" => $filters,
            "routePath" => $metaData->getBreadcrumb()[count($metaData->getBreadcrumb()) - 2]->getRoute()
        ]);

                // $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
        return $this->render('clothes/index.html.twig', [
            'metaData' => $metaData,
            'block' => $block,
        ]);
    }
}
