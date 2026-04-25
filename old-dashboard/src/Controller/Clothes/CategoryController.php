<?php

namespace App\Controller\Clothes;

use DateTimeImmutable;
use App\Entity\Category\Category;
use Symfony\UX\Turbo\TurboBundle;
use App\Service\UploadedFileService;
use App\Entity\Collections\Collections;
use Doctrine\ORM\EntityManagerInterface;
use App\DTO\Clothes\Category\CategoryDTO;
use Symfony\Component\HttpFoundation\Request;
use App\Form\Clothes\Category\NewCategoryForm;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\Clothes\Category\EditCategoryForm;
use App\Provider\PageMetadata\PageMetadataProvider;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CategoryController extends AbstractController
{


//? ---- index
    #[Route('/clothes/category', name: 'app_clothes_category')]
    public function index(
        PageMetadataProvider $pageMetadata, 
        Request $request,
        EntityManagerInterface $entityManagerInterface,
    ): Response
    {
        $metaData = $pageMetadata->getPageMetada($request->attributes->get('_route'));
        $categories = $entityManagerInterface->getRepository(Category::class)->findAll();

        $block = $this->renderBlockView('clothes/category/turbo/index.html.twig','index',[
            "categories" => $categories,
            "routePath" => $metaData->getBreadcrumb()[count($metaData->getBreadcrumb()) - 2]->getRoute()
        ]);

                // $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
        return $this->render('clothes/category/index.html.twig', [
            'metaData' => $metaData,
            'block' => $block,
        ]);
    }
//? ---- new
    #[Route('/clothes/category/new', name: 'app_clothes_category_new')]
    public function new(
        // PageMetadataProvider $pageMetadata, 
        Request $request,
        EntityManagerInterface $entityManagerInterface,
        SluggerInterface $slugger,

        #[Autowire('%kernel.project_dir%/public/upload/clothes/category')] string $targetDirectory
    ): Response
    {
        $categoryDTO = new CategoryDTO();
        $form = $this->createForm(NewCategoryForm::class, $categoryDTO);

        $form->handleRequest($request);
        
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        if($form->isSubmitted() && $form->isValid()){
            $today = new \DateTimeImmutable();
            $category = new Category();
            $category
                ->setCreatedAt($today)
                ->setEditedAt($today)
                ->setName($categoryDTO->getName())
                ->setMetaDescription($categoryDTO->getMetaDescription())
                ->setSlug($slugger->slug($categoryDTO->getName()))
                ->setSlug($slugger->slug($categoryDTO->getName()))
                ->setIsOnline(false)
            ;

            /** @var UploadedFile $categoryImage */
            $categoryImage = $categoryDTO->getImage();
            
            if ($categoryImage) {
                $originalFilename = pathinfo($categoryImage->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$categoryImage->guessExtension();
                $categoryImage->move($targetDirectory,$newFilename);
                $category->setImage(explode('public/',$targetDirectory.'/'.$newFilename)[1]);
            }

            $entityManagerInterface->persist($category);

            foreach ($categoryDTO->getCollections() as $collectionData) {
                $collection = new Collections();
                $collection
                    ->setName($collectionData->getName())
                    ->setCategory($category)
                    ->setCreatedAt($today)
                    ->setEditedAt($today)
                    ->setIsOnline(false)
                ;
                $entityManagerInterface->persist($collection);
            }


            $entityManagerInterface->flush();
            $this->addFlash('sucess', 'La category '.$category->getName().' a bien été ajouté. ');
            foreach ($categoryDTO as $collectionData) {
                
                $this->addFlash('sucess', 'La collection '.$collectionData->getName().' a bien été ajouté.');
            }

            return $this->renderBlock('clothes/category/turbo/new.html.twig','new_success',[
                'categorie' => $category
            ]);
        }


        return $this->renderBlock('clothes/category/turbo/new.html.twig','new',[
            'form'=>$form
        ]);
    }

    #[Route('/clothes/category/cancelnew', name: 'app_clothes_category_cancel_new')]
    public function cancelnew(
        Request $request,
    ): Response
    {
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
        return $this->renderBlock('clothes/category/turbo/new.html.twig','cancel_new',[]);
    }
//? ---- show
    #[Route('/clothes/category/{id}', name: 'app_clothes_category_show', requirements: ['id' => '\d+'])]
    public function show(
        Category $category, 
        Request $request,
        EntityManagerInterface $entityManagerInterface,
        SluggerInterface $slugger,

        #[Autowire('%kernel.project_dir%/public/upload/clothes/category')] string $targetDirectory
    ): Response
    {
        // $categoryDTO = new CategoryDTO();
        // $form = $this->createForm(NewCategoryForm::class, $categoryDTO);

        // $form->handleRequest($request);
        
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        // if($form->isSubmitted() && $form->isValid()){

        //     $category = new Category();
        //     $category
        //         ->setName($categoryDTO->getName())
        //         ->setMetaDescription($categoryDTO->getMetaDescription())
        //         ->setSlug($slugger->slug($categoryDTO->getName()))
        //         ->setIsOnline(false)
        //     ;

        //     /** @var UploadedFile $categoryImage */
        //     $categoryImage = $categoryDTO->getImage();
            
        //     if ($categoryImage) {
        //         $originalFilename = pathinfo($categoryImage->getClientOriginalName(), PATHINFO_FILENAME);
        //         // this is needed to safely include the file name as part of the URL
        //         $safeFilename = $slugger->slug($originalFilename);
        //         $newFilename = $safeFilename.'-'.uniqid().'.'.$categoryImage->guessExtension();
        //         $categoryImage->move($targetDirectory,$newFilename);
        //         $category->setImage(explode('public/',$targetDirectory.'/'.$newFilename)[1]);
        //     }
        //     $entityManagerInterface->persist($category);

        //     foreach ($categoryDTO as $collectionData) {
        //         $collection = new Collections();
        //         $collection->setName($collectionData->getName());
        //         $collection->setCategory($category);
        //         $entityManagerInterface->persist($collection);
        //     }


        //     $entityManagerInterface->flush();
        //     $this->addFlash('sucess', 'La category '.$category->getName().' a bien été ajouté. ');
        //     foreach ($categoryDTO as $collectionData) {
                
        //         $this->addFlash('sucess', 'La collection '.$collectionData->getName().' a bien été ajouté.');
        //     }

        //     return $this->renderBlock('clothes/category/turbo/new.html.twig','new_success',[
        //         'category' => $category
        //     ]);
        // }


        return $this->renderBlock('clothes/category/turbo/show.html.twig','show_category',[
            'category' => $category
        ]);
    }
//? ---- edit
    #[Route('/clothes/category/edit/{id}', name: 'app_clothes_category_edit')]
    public function edit(
        // PageMetadataProvider $pageMetadata, 
        Request $request,
        EntityManagerInterface $entityManagerInterface,
        SluggerInterface $slugger,
        Category $category,
        #[Autowire('%kernel.project_dir%/public/upload/clothes/category')] string $targetDirectory
    ): Response
    {
        $categoryDTO = new CategoryDTO();
        $categoryDTO
            ->setName($category->getName())
            ->setMetaDescription($category->getMetaDescription())
        ;
        $form = $this->createForm(EditCategoryForm::class, $categoryDTO);

        $form->handleRequest($request);
        
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        if($form->isSubmitted() && $form->isValid()){
            $today = new \DateTimeImmutable();

            $category
                ->setEditedAt($today)
                ->setName($categoryDTO->getName())
                ->setMetaDescription($categoryDTO->getMetaDescription())
                ->setSlug($slugger->slug($categoryDTO->getName()))
            ;

            /** @var UploadedFile $categoryImage */
            $categoryImage = $categoryDTO->getImage();
            
            if ($categoryImage) {
                $originalFilename = pathinfo($categoryImage->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$categoryImage->guessExtension();
                $categoryImage->move($targetDirectory,$newFilename);
                $category->setImage(explode('public/',$targetDirectory.'/'.$newFilename)[1]);
            }
            
            $entityManagerInterface->persist($category);

            $entityManagerInterface->flush();
            $this->addFlash('sucess', 'La category '.$category->getName().' a bien été modifier.');
            // foreach ($categoryDTO as $collectionData) {
                
            //     $this->addFlash('sucess', 'La collection '.$collectionData->getName().' a bien été modifier.');
            // }

            return $this->renderBlock('clothes/category/turbo/edit.html.twig','success_edit_category',[
                'category' => $category
            ]);
        }


        return $this->renderBlock('clothes/category/turbo/edit.html.twig','edit_category_form',[
            'form'=>$form,
            'category' => $category
        ]);
    }

    #[Route('/clothes/category/canceledit', name: 'app_clothes_category_edit_cancel')]
    public function canceledit(
        Request $request,
    ): Response
    {
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
        return $this->renderBlock('clothes/category/turbo/edit.html.twig','delete_new_category_form',[]);
    }

//? ---- delete
    #[Route('/clothes/category/delete/{id}', name: 'app_clothes_category_delete')]
    public function delete(
        int $id, 
        Request $request,
        EntityManagerInterface $entityManagerInterface,

    ): Response
    {
        if (!$this->isCsrfTokenValid('delete-category', $request->getPayload()->getString('_token'))) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }
        
        $category = $entityManagerInterface->getRepository(Category::class)->findOneBy(['id'=>$id]);

        
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
        $entityManagerInterface->remove($category);
        $entityManagerInterface->flush();


        return $this->renderBlock('clothes/category/turbo/delete.html.twig','delete_category',[
            'categoryId'=>$id
        ]);
    }
//? ---- 
}
