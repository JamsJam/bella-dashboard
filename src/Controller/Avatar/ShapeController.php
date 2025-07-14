<?php

namespace App\Controller\Avatar;

use App\Enum\Avatar\BodyPartEnum;
use Symfony\UX\Turbo\TurboBundle;
use App\DTO\Avatar\Color\ColorDTO;
use App\DTO\Avatar\Shape\ShapeDTO;
use App\Form\Avatar\Shape\NewShapeForm;
use App\Form\Avatar\Color\EditColorForm;
use App\Form\Avatar\Shape\EditShapeForm;
use Doctrine\ORM\EntityManagerInterface;
use App\DTO\Breadcrumb\BreadcrumbItemDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Resolver\Avatar\BodyPartRegistryResolver;
use App\Provider\PageMetadata\PageMetadataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ShapeController extends AbstractController
{
    #[Route('/avatar/shape', name: 'app_avatar_shape_index')]
    public function index(
        PageMetadataProvider $pageMetadata,
        Request $request,
        ?BodyPartEnum $type,
        EntityManagerInterface $entityManagerInterface,
        BodyPartRegistryResolver $bodyPartRegistryResolver,
        ): Response
    {

        $type =  BodyPartEnum::tryFrom($request->query->get('type'));
        $parts = null;
        if (BodyPartEnum::BODY === $type || BodyPartEnum::SKIN === $type) {
            $type = null;
        }

        $metaData = $pageMetadata->getPageMetada($request->attributes->get('_route'));

        if (null !== $type) {
            $metaData->addBreadcrumb((new BreadcrumbItemDTO())
                ->setTitle($type->value)
                ->setRoute('app_avatar_shape_index')
            );
            $entity = $bodyPartRegistryResolver->getEntity('shape', $type->value);
            $allPart = $entityManagerInterface->getRepository($entity)->findAll();
            
            return $this->render('avatar/shape/index.html.twig', [
                'metaData' => $metaData,
                'type' => $type->value,
                'parts' => $parts,
                'shapeItems' => $allPart,
            ]);
        }

        $typeChoice = [
            'hair' => 'formes de cheveux',
            'mouth' => 'formes de bouche',
            'eye' => 'formes des yeux',
            'eyebrows' => 'formes des sourcils',
            'nose' => 'formes de nez',
            'face' => 'formes de visage',
        ];

        // type=null (Avatar Hub)
        return $this->render('avatar/shape/index.html.twig', [
            'metaData' => $metaData,
            'type' => $type,
            'shapeChoice' => $typeChoice,
            'parts' => $parts,
        ]);
    }

//? --------- new
    #[Route('/avatar/shape/new', name: 'app_avatar_shape_new', methods:["GET", "POST"])]
    public function new(
        Request $request,
        BodyPartRegistryResolver $bodyPartRegistryResolver,
        EntityManagerInterface $entityManagerInterface
    )
    {
        
        $part = $request->query->get('type');

        $form = $this->createForm(NewShapeForm::class, new ShapeDTO(),[
            "action" => $this->generateUrl('app_avatar_shape_new',[
                'type' => $part
            ])
        ])
            
        ;

        $form->handleRequest($request);

        // if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $bodyPartRegistryResolver->getEntity('shape', $part);
            
            $shape =  $form->getData();

            $newShape = new $entity ;
            $today = new \DateTimeImmutable();
            $newShape
                ->setName(strtolower($shape->getName()))
                ->setCreatedAt($today)
                ->setEditedAt($today)
            ;
            $entityManagerInterface->persist($newShape);
            $entityManagerInterface->flush();

            $this->addFlash(
                'add-success',
                'La forme "'.$newShape->getName().'" à bien été ajouté'
            );

            
            return $this->renderBlock('avatar/shape/turbo/new.html.twig', 'success_add_shape', [
                'shape' => $newShape,
                'type' => $part,
            ]);
        }

        return $this->renderBlock('avatar/shape/turbo/new.html.twig', 'new_shape_form', [

            'form' => $form,
            'type' => $part,
        ]);
        
    }

    #[Route('/avatar/shape/cancel/new', name: 'app_avatar_shape_new_cancel',methods:["GET"])]
    public function cancel_new(
        Request $request
    )
    {
        $type = $request->query->get('type');
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->renderBlock('avatar/shape/turbo/new.html.twig', 'delete_new_shape_form', [
                
                'type' => $type,
            ]);
    }


//? --------- edit
    #[Route('/avatar/shape/edit/{id}', name: 'app_avatar_shape_edit', methods:["GET","POST"])]
    public function edit(
        int $id,
        Request $request,
        BodyPartRegistryResolver $bodyPartRegistryResolver,
        EntityManagerInterface $entityManagerInterface
    ):Response
    {
        $type = $request->query->get('type');
        $entity = $bodyPartRegistryResolver->getEntity('shape',$type);
        $shape = $entityManagerInterface->getRepository($entity)->findOneBy(["id"=>$id]) ;
        $dtoShape = new ShapeDTO();
        $dtoShape
            ->setName($shape->getName());
            
            
        $form = $this->createForm(EditShapeForm::class, $dtoShape);

        $form->handleRequest($request);

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        if($form->isSubmitted() && $form->isValid()){
            $today = new \DateTimeImmutable();
            
            $shape
                ->setEditedAt($today)
                ->setName(strtolower($form->getData()->getName()))  
            ;
            $entityManagerInterface->persist($shape);
            $entityManagerInterface->flush();

            return $this->renderBlock('avatar/shape/turbo/edit.html.twig','success_edit_shape',[
                "shape" => $shape,
                'type' => $type,
                "id" => $id,

            ]);
        }


        

        return $this->renderBlock('avatar/shape/turbo/edit.html.twig','edit_shape_form',[
            "shape" => $shape,
            'type' => $type,
            "id" => $id,
            'form' => $form
        ]);
    }

    #[Route('/avatar/shape/cancel/edit', name: 'app_avatar_shape_edit_cancel',methods:["GET"])]
    public function cancel_edit(
        Request $request
    ):Response
    {
        $type = $request->query->get('type');
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->renderBlock('avatar/shape/turbo/edit.html.twig', 'delete_edit_shape_form', [
                
                'type' => $type,
            ]);
    }

//? --------- delete
    #[Route('/avatar/shape/delete/{id}', name: 'app_avatar_shape_delete')]
    public function delete(
        int $id,
        Request $request,
        BodyPartRegistryResolver $bodyPartRegistryResolver,
        EntityManagerInterface $entityManagerInterface
    ){


        if(!$this->isCsrfTokenValid("delete-shape",$request->getPayload()->getString('_token'))){
            return new Response("", RESPONSE::HTTP_BAD_REQUEST);
        }
        $type = $request->query->get('type');
        $shapeEntity = $bodyPartRegistryResolver->getEntity("shape",$type);
        $shape = $entityManagerInterface->getRepository($shapeEntity)->findOneBy(['id'=> $id]);
        // dd($shape);
        $entityManagerInterface->remove($shape);
        
        //?------ delete body part related to this shape

        $entity = $bodyPartRegistryResolver->getEntity("body",$type);
        $elements = $entityManagerInterface->getRepository($entity)->findBy(["shape" => $shape]);


        if(count($elements) > 0){
            foreach ($elements as  $element) {
                $entityManagerInterface->remove($element);
            }
        }
        
        $entityManagerInterface->flush();

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            
        return $this->renderBlock('avatar/shape/turbo/delete.html.twig', 'delete_shape', [
            'shapeId' => $id,
            'type' => $type,
        ]);
    }
}
