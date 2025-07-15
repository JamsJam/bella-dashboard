<?php

namespace App\Controller\Avatar;

use App\Form\Avatar\BodysizeForm;
use Symfony\UX\Turbo\TurboBundle;
use App\Entity\Avatar\Body\Bodysize;
use App\Form\Avatar\MorphologieForm;
use App\Entity\Avatar\Body\Morphologie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Provider\PageMetadata\PageMetadataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class BodysizeController extends AbstractController
{
//? --------- index
    #[Route('/avatar/bodysize', name: 'app_avatar_bodysize')]
    public function index(
        PageMetadataProvider $pageMetadata,
        Request $request,
        EntityManagerInterface $entityManagerInterface,
    ): Response
    {
        $metaData = $pageMetadata->getPageMetada($request->attributes->get('_route'));
        $bodysizes = $entityManagerInterface->getRepository(Bodysize::class)->findAll();

        return $this->render('avatar/bodysize/index.html.twig', [
            'metaData' => $metaData,
            "bodysizes" =>  $bodysizes
        ]);
    }

//? --------- new
    #[Route('/avatar/bodysize/new', name: 'app_avatar_bodysize_new', methods:["GET", "POST"])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManagerInterface
    ):Response
    {

        $form = $this->createForm(BodysizeForm::class, new Bodysize(),[
            "action" => $this->generateUrl('app_avatar_bodysize_new',)
        ]);

        $form->handleRequest($request);

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        if ($form->isSubmitted() && $form->isValid()) {

            
            $bodysize  =  $form->getData();

            $newBodysize = new Bodysize ;
            $today = new \DateTimeImmutable();
            $newBodysize
                ->setName(strtolower($bodysize ->getName()))
                ->setCreatedAt($today)
                ->setEditedAt($today)
            ;
            $entityManagerInterface->persist($newBodysize);
            $entityManagerInterface->flush();

            $this->addFlash(
                'add-success',
                'La forme "'.$newBodysize->getName().'" à bien été ajouté'
            );

            
            return $this->renderBlock('avatar/bodysize/turbo/new.html.twig', 'success_add_bodysize', [
                'bodysize' => $newBodysize,

            ]);
        }

        return $this->renderBlock('avatar/bodysize/turbo/new.html.twig', 'new_bodysize_form', [

            'form' => $form,

        ]);
        
    }

    #[Route('/avatar/bodysize/cancel/new', name: 'app_avatar_bodysize_new_cancel',methods:["GET"])]
    public function cancel_new(
        Request $request
    )
    {

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return $this->renderBlock('avatar/bodysize/turbo/new.html.twig', 'delete_new_bodysize_form');
    }

//? --------- edit
    #[Route('/avatar/bodysize/edit/{id}', name: 'app_avatar_bodysize_edit', methods:["GET","POST"])]
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $entityManagerInterface
    ):Response
    {

        $morphology = $entityManagerInterface->getRepository(Bodysize::class)->findOneBy(["id"=>$id]) ;

        $form = $this->createForm(MorphologieForm::class, $morphology);
        $form->handleRequest($request);

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        if($form->isSubmitted() && $form->isValid()){
            $today = new \DateTimeImmutable();
            
            $morphology
                ->setEditedAt($today)
                ->setName(strtolower($form->getData()->getName()))  
            ;
            $entityManagerInterface->persist($morphology);
            $entityManagerInterface->flush();

            return $this->renderBlock('avatar/bodysize/turbo/edit.html.twig','success_edit_bodysize',[
                "morphologie" => $morphology,
                "id" => $id,

            ]);
        }
        return $this->renderBlock('avatar/bodysize/turbo/edit.html.twig','edit_bodysize_form',[
            "morphologie" => $morphology,
            "id" => $id,
            'form' => $form
        ]);
    }

    #[Route('/avatar/bodysize/cancel/edit', name: 'app_avatar_bodysize_edit_cancel',methods:["GET"])]
    public function cancel_edit(
        Request $request
    ):Response
    {
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return $this->renderBlock('avatar/bodysize/turbo/edit.html.twig', 'delete_edit_color_form');
    }

//? --------- delete
    #[Route('/avatar/bodysize/delete/{id}', name: 'app_avatar_bodysize_delete')]
    public function delete(
        int $id,
        Request $request,
        EntityManagerInterface $entityManagerInterface
    ){

        if(!$this->isCsrfTokenValid("delete-bodysize",$request->getPayload()->getString('_token'))){
            return new Response("", RESPONSE::HTTP_BAD_REQUEST);
        }
        
        
        $bodysize = $entityManagerInterface->getRepository(Bodysize::class)->findOneBy(['id'=> $id]);
        $entityManagerInterface->remove($bodysize);
        

        $entityManagerInterface->flush();

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
            
        return $this->renderBlock('avatar/bodysize/turbo/delete.html.twig', 'delete_bodysize', [
            'bodysizeId' => $id,

        ]);
    }
}
