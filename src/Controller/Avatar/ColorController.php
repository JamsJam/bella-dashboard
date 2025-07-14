<?php

namespace App\Controller\Avatar;


use App\Enum\Avatar\BodyPartEnum;
use Symfony\UX\Turbo\TurboBundle;
use App\DTO\Avatar\Color\ColorDTO;
use App\Form\Avatar\Color\NewColorForm;
use App\Form\Avatar\Color\EditColorForm;
use Doctrine\ORM\EntityManagerInterface;
use App\DTO\Breadcrumb\BreadcrumbItemDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Resolver\Avatar\BodyPartRegistryResolver;
use App\Provider\PageMetadata\PageMetadataProvider;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ColorController extends AbstractController
{

//? --------- index
    #[Route('/avatar/color', name: 'app_avatar_color_index' , methods:["GET"])]
    public function index(
        PageMetadataProvider $pageMetadata,
        Request $request,
        ?BodyPartEnum $type,
        EntityManagerInterface $entityManagerInterface,
        BodyPartRegistryResolver $bodyPartRegistryResolver,
    ): Response
    {
        // dd($request->query->get('type'));
        // ? ==== define type
        $type =  BodyPartEnum::tryFrom($request->query->get('type'));
        $parts = null;
        if (BodyPartEnum::FACE === $type || BodyPartEnum::NOSE === $type || BodyPartEnum::BODY === $type) {
            $type = null;
        }

        $metaData = $pageMetadata->getPageMetada($request->attributes->get('_route'));

        if (null !== $type) {
            $metaData->addBreadcrumb((new BreadcrumbItemDTO())
            ->setTitle($type->value)
            ->setRoute('app_avatar_index')
            );

            $entity = $bodyPartRegistryResolver->getEntity('color', $type->value);
            $allPart = $entityManagerInterface->getRepository($entity)->findAll();


            // type!=null (Avatar bodypart index)
            return $this->render('avatar/color/index.html.twig', [
                'metaData' => $metaData,
                'type' => $type->value,
                'parts' => $parts,
                'colorItems' => $allPart,

            ]);
        }

        $typeChoice = [
            'skin' => 'couleur de peau',
            'hair' => 'couleur de cheveux',
            'mouth' => 'couleur de bouche',
            'eye' => 'couleur des yeux',
            'eyebrows' => 'couleur des sourcils',
        ];

        // type=null (Avatar Hub)
        return $this->render('avatar/color/index.html.twig', [
            'metaData' => $metaData,
            'type' => $type,
            'colorChoice' => $typeChoice,
            'parts' => $parts,
        ]);
    }

//? --------- new
    #[Route('/avatar/color/new', name: 'app_avatar_color_new', methods:["GET", "POST"])]
    public function new(
        Request $request,
        BodyPartRegistryResolver $bodyPartRegistryResolver,
        EntityManagerInterface $entityManagerInterface
    )
    {
        
        $part = $request->query->get('type');

        $form = $this->createForm(NewColorForm::class, new ColorDTO(),[
            "action" => $this->generateUrl('app_avatar_color_new',[
                'type' => $part
            ])
        ])
            
        ;

        $form->handleRequest($request);

        // if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $bodyPartRegistryResolver->getEntity('color', $part);
            
            $color =  $form->getData();

            $newColor = new $entity ;
            $today = new \DateTimeImmutable();
            $newColor
                ->setName(strtolower($color->getName()))
                ->setHexa(strtolower(substr($color->getHexa(),1)))
                ->setCreatedAt($today)
                ->setEditedAt($today)
            ;
            $entityManagerInterface->persist($newColor);
            $entityManagerInterface->flush();

            $this->addFlash(
                'add-success',
                'La couleur '.$newColor->getName().' à bien été ajouté'
            );

            
            return $this->renderBlock('avatar/color/turbo/new.html.twig', 'success_add_color', [
                'color' => $newColor,
                'type' => $part,
            ]);
        }

        return $this->renderBlock('avatar/color/turbo/new.html.twig', 'new_color_form', [

            'form' => $form,
            'type' => $part,
        ]);
        
    }

    #[Route('/avatar/color/cancel/new', name: 'app_avatar_color_new_cancel',methods:["GET"])]
    public function cancel_new(
        Request $request
    )
    {
        $type = $request->query->get('type');
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->renderBlock('avatar/color/turbo/new.html.twig', 'delete_new_color_form', [
                
                'type' => $type,
            ]);
    }

//? --------- edit
    #[Route('/avatar/color/edit/{id}', name: 'app_avatar_color_edit', methods:["GET","POST"])]
    public function edit(
        int $id,
        Request $request,
        BodyPartRegistryResolver $bodyPartRegistryResolver,
        EntityManagerInterface $entityManagerInterface
    ):Response
    {
        $type = $request->query->get('type');
        $entity = $bodyPartRegistryResolver->getEntity('color',$type);
        $color = $entityManagerInterface->getRepository($entity)->findOneBy(["id"=>$id]) ;
        $dtoColor = new ColorDTO();
        $dtoColor
            ->setName($color->getName())
            ->setHexa($color->getHexa() !== null ? "#".$color->getHexa() : "#000000");

        $form = $this->createForm(EditColorForm::class, $dtoColor);

        $form->handleRequest($request);

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        if($form->isSubmitted() && $form->isValid()){
            $today = new \DateTimeImmutable();
            
            
            $color
                ->setEditedAt($today)
                ->setHexa(strtolower(substr($form->getData()->getHexa(),1)))  
            ;
            $entityManagerInterface->persist($color);
            $entityManagerInterface->flush();

            return $this->renderBlock('avatar/color/turbo/edit.html.twig','success_edit_color',[
                "color" => $color,
                'type' => $type,
                "id" => $id,

            ]);
        }


        

        return $this->renderBlock('avatar/color/turbo/edit.html.twig','edit_color_form',[
            "color" => $color,
            'type' => $type,
            "id" => $id,
            'form' => $form
        ]);
    }

    #[Route('/avatar/color/cancel/edit', name: 'app_avatar_color_edit_cancel',methods:["GET"])]
    public function cancel_edit(
        Request $request
    ):Response
    {
        $type = $request->query->get('type');
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->renderBlock('avatar/color/turbo/edit.html.twig', 'delete_edit_color_form', [
                
                'type' => $type,
            ]);
    }

//? --------- delete
    #[Route('/avatar/color/delete/{id}', name: 'app_avatar_color_delete')]
    public function delete(
        int $id,
        Request $request,
        BodyPartRegistryResolver $bodyPartRegistryResolver,
        EntityManagerInterface $entityManagerInterface
    ){
        // dd($request->getPayload()->getString('_token'));

        if(!$this->isCsrfTokenValid("delete-color",$request->getPayload()->getString('_token'))){
            return new Response("", RESPONSE::HTTP_BAD_REQUEST);
        }
        $type = $request->query->get('type');
        $colorEntity = $bodyPartRegistryResolver->getEntity("color",$type);
        $color = $entityManagerInterface->getRepository($colorEntity)->findOneBy(['id'=> $id]);
        // dd($color);
        $entityManagerInterface->remove($color);
        
        //?------ delete body part related to this color
        if ($type === "skin") {
            foreach (["face", "nose", "body"] as  $value) {
                $entity = $bodyPartRegistryResolver->getEntity("body",$value);
                $elements = $entityManagerInterface->getRepository($entity)->findBy(["color" => $color]);
                if(count($elements) > 0){

                    foreach ($elements as  $element) {
                        $entityManagerInterface->remove($element);
                    }
                }
                
            }

            
        }else{
            
            $entity = $bodyPartRegistryResolver->getEntity("body",$type);
            $elements = $entityManagerInterface->getRepository($entity)->findBy(["color" => $color]);
            // dd($elements);

            if(count($elements) > 0){
                foreach ($elements as  $element) {
                    $entityManagerInterface->remove($element);
                }
            }
            

        }
        
        $entityManagerInterface->flush();


        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            
        return $this->renderBlock('avatar/color/turbo/delete.html.twig', 'delete_color', [
            'colorId' => $id,
            'type' => $type,
        ]);
    }
}
