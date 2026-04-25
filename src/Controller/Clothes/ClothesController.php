<?php

namespace App\Controller\Clothes;

use App\Service\ThemeService;
use App\Entity\Clothes\Clothes;
use App\Entity\Category\Category;
use Symfony\UX\Turbo\TurboBundle;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Collections\Collections;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\Clothes\ClothesRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Provider\PageMetadata\PageMetadataProvider;
use App\Application\PageGenerator\Services\PageService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ClothesController extends AbstractController
{
    #[Route('/clothes', name: 'app_clothes')]
    public function index(
        PageMetadataProvider $pageMetadata,
        Request $request,
        EntityManagerInterface $entityManagerInterface,
        ThemeService $theme_service,
        ClothesRepository $clothesRepository,
        PageService $pageService ,
        #[Autowire('%kernel.project_dir%/src/Pages/Back/Clothes/')] string $yamlFilePath,
    ): Response
    {
        // $metaData = $pageMetadata->getPageMetada($request->attributes->get('_route'));
        $theme = $theme_service->getTheme();

        // $page = intval($request->query->get('page') ?? 1);
        // $offset = $page - 1;
        // $maxsizelist = 10;

        // $clothes = $entityManagerInterface->getRepository(Clothes::class)->findDistinctBySlug();

        // $totalClothes =  count($clothes);
        // $clothesList = array_slice($clothes, $offset*$maxsizelist ,$maxsizelist);
        
        // $countPage = intval(ceil($totalClothes/$maxsizelist));
        $sortBy = $request->query->get("sortBy");
        $sortDirection = $request->query->get("direction");

        $pageView = $pageService->createPageFromYamlFile($yamlFilePath . "index.yaml", ["sortBy" =>$sortBy, "direction" => $sortDirection]);
        
        foreach ($pageView->getBlocks() as $block) {

            dump($block);
        }
            // dump($pageView);
        

        // $filters = [
        //     "Categories" => $entityManagerInterface->getRepository(Category::class)->findAll(),
        //     "Collections" => $entityManagerInterface->getRepository(Collections::class)->findAll(),
        //     "Couleurs" => $entityManagerInterface->getRepository(Clothescolor::class)->findAll()
        // ] ;


        // $block = $this->renderBlockView('clothes/clothes/turbo/index.html.twig','index',[
        //     "clothes" => $clothes,
        //     "filters" => $filters,
        //     "routePath" => $metaData->getBreadcrumb()[count($metaData->getBreadcrumb()) - 2]->getRoute()
        // ]);

                // $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
        return $this->render('clothes/index.html.twig', [
            // 'metaData' => $metaData,
            // 'block' => $block,
            'theme' => $theme,
            // 'clothes' => $clothesList,
            // 'itemCount' => $totalClothes,
            // 'sizeList' => $maxsizelist,
            // 'page' => $page,
            'pageView' => $pageView,
            // 'pageCount' => $countPage,
        ]);
    }
}
