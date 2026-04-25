<?php

namespace App\Controller\Clothes;

use Symfony\UX\Turbo\TurboBundle;

use App\Entity\Collections\Collections;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\DTO\Clothes\Collections\CollectionsDTO;
use App\Factory\Collections\CollectionsFactory;
use App\Service\FileService\FileManagerService;
use App\Provider\PageMetadata\PageMetadataProvider;
use App\Form\Clothes\Collections\NewCollectionsForm;
use App\Form\Clothes\Collections\EditCollectionsForm;
use App\Repository\Clothes\ClothesRepository;
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

    #[Route('/clothes/collections/new', name: 'app_clothes_collections_new')]
    public function new(
        // PageMetadataProvider $pageMetadata, 
        Request $request,
        EntityManagerInterface $entityManagerInterface,
        CollectionsFactory $collectionsFactory
    ): Response
    {
        $collectionDTO = new CollectionsDTO;
        $form = $this->createForm(NewCollectionsForm::class, $collectionDTO);
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            
            $displayClotheImageIndex = []; 

            foreach ($request->getPayload() as $key => $value) {
                if (preg_match('/^front-clothe-(\d+)-file$/', $key, $matches)) {
                    $displayClotheImageIndex[] = (int) $value;
                }
            }
            /** @var Collections $collection */
            $collection = $collectionsFactory->createCollection($collectionDTO,false,$displayClotheImageIndex);

            $entityManagerInterface->persist($collection);
            $entityManagerInterface->flush();

            return $this->renderBlock('clothes/collections/turbo/new.html.twig','new_success',[
                'collection'=> $collection
            ]);
        }

        return $this->renderBlock('clothes/collections/turbo/new.html.twig','new',[
            'form' => $form
        ]);
        
    }

     #[Route('/clothes/collections/edit/{id:collection}', name: 'app_clothes_collections_edit')]
    public function edit(
        // PageMetadataProvider $pageMetadata, 
        Request $request,
        EntityManagerInterface $entityManagerInterface,
        CollectionsFactory $collectionsFactory,
        Collections $collection

    ): Response
    {

        $collectionDTO = new CollectionsDTO();
        $collectionDTO
            ->setName($collection->getName())
            ->setCategory($collection->getCategory())
            ->setImage($collection->getImage())
            ;
        
        $this->createForm(EditCollectionsForm::class,$collectionDTO);

        return $this->renderBlock('clothes/collections/turbo/edit.html.twig','edit',[

        ]);
    }

     #[Route('/clothes/collections/show/{id:collection}', name: 'app_clothes_collections_show')]
    public function show(
        // PageMetadataProvider $pageMetadata, 
        // Request $request,
        // EntityManagerInterface $entityManagerInterface,
        // CollectionsFactory $collectionsFactory,
        Collections $collection,
        ClothesRepository $clothesRepository

    ): Response
    {
        $clothesRepository->findClothesInCollection($collection);
        $clothes = $clothesRepository->findAll(['collection' => $collection]);

    

        

        return $this->renderBlock('clothes/collections/turbo/show.html.twig','show_collection',[
            'collection' => $collection,
            'clothes' => $clothes,
        ]);
    }

    #[Route('/clothes/collections/delete/{id:collection}', name: 'app_clothes_collections_delete')]
    public function delete(
        // PageMetadataProvider $pageMetadata, 
        EntityManagerInterface $entityManagerInterface,
        FileManagerService $fileManagerService,
        Collections $collection

    ): Response
    {


        try {
            //code...
            $collectionImage = $collection->getImage();
            $fileManagerService-> removeFile("/public".$collectionImage);
    
            $this->addFlash('success', "La collection ".$collection->getName()." a bien été supprimée");
            $entityManagerInterface->remove($collection);
            $entityManagerInterface->flush();
        } catch (\Throwable $th) {

            $this->addFlash('error', "Un probleme est survenue lors de l'opération:  ". $th->getMessage());

        }



        return $this->renderBlock('clothes/collections/turbo/delete.html.twig','delete',[

        ]);
    }
};


