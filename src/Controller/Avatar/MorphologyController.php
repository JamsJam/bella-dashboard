<?php

namespace App\Controller\Avatar;

use App\Entity\Avatar\Body\Morphologie;
use App\Form\Avatar\MorphologieForm;
use App\Provider\PageMetadata\PageMetadataProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Turbo\TurboBundle;

final class MorphologyController extends AbstractController
{
    // ? --------- index
    #[Route('/avatar/morphology', name: 'app_avatar_morphology')]
    public function index(
        PageMetadataProvider $pageMetadata,
        Request $request,
        EntityManagerInterface $entityManagerInterface,
    ): Response {
        $metaData = $pageMetadata->getPageMetada($request->attributes->get('_route'));
        $morphologies = $entityManagerInterface->getRepository(Morphologie::class)->findAll();

        return $this->render('avatar/morphology/index.html.twig', [
            'metaData' => $metaData,
            'morphologies' => $morphologies,
        ]);
    }

    // ? --------- new
    #[Route('/avatar/morphology/new', name: 'app_avatar_morphology_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManagerInterface,
    ): Response {
        $form = $this->createForm(MorphologieForm::class, new Morphologie(), [
            'action' => $this->generateUrl('app_avatar_morphology_new'),
        ]);

        $form->handleRequest($request);

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        if ($form->isSubmitted() && $form->isValid()) {
            $morphology = $form->getData();

            $newMorphology = new Morphologie();
            $today = new \DateTimeImmutable();
            $newMorphology
                ->setName(strtolower($morphology->getName()))
                ->setCreatedAt($today)
                ->setEditedAt($today)
            ;
            $entityManagerInterface->persist($newMorphology);
            $entityManagerInterface->flush();

            $this->addFlash(
                'add-success',
                'La forme "'.$newMorphology->getName().'" à bien été ajouté'
            );

            return $this->renderBlock('avatar/morphology/turbo/new.html.twig', 'success_add_morphology', [
                'morphologie' => $newMorphology,
            ]);
        }

        return $this->renderBlock('avatar/morphology/turbo/new.html.twig', 'new_morphology_form', [
            'form' => $form,
        ]);
    }

    #[Route('/avatar/morphology/cancel/new', name: 'app_avatar_morphology_new_cancel', methods: ['GET'])]
    public function cancel_new(
        Request $request,
    ) {
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return $this->renderBlock('avatar/morphology/turbo/new.html.twig', 'delete_new_morphology_form');
    }

    // ? --------- edit
    #[Route('/avatar/morphology/edit/{id}', name: 'app_avatar_morphology_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $entityManagerInterface,
    ): Response {
        $morphology = $entityManagerInterface->getRepository(Morphologie::class)->findOneBy(['id' => $id]);

        $form = $this->createForm(MorphologieForm::class, $morphology);
        $form->handleRequest($request);

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        if ($form->isSubmitted() && $form->isValid()) {
            $today = new \DateTimeImmutable();

            $morphology
                ->setEditedAt($today)
                ->setName(strtolower($form->getData()->getName()))
            ;
            $entityManagerInterface->persist($morphology);
            $entityManagerInterface->flush();

            return $this->renderBlock('avatar/morphology/turbo/edit.html.twig', 'success_edit_morphology', [
                'morphologie' => $morphology,
                'id' => $id,
            ]);
        }

        return $this->renderBlock('avatar/morphology/turbo/edit.html.twig', 'edit_morphology_form', [
            'morphologie' => $morphology,
            'id' => $id,
            'form' => $form,
        ]);
    }

    #[Route('/avatar/morphology/cancel/edit', name: 'app_avatar_morphology_edit_cancel', methods: ['GET'])]
    public function cancel_edit(
        Request $request,
    ): Response {
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return $this->renderBlock('avatar/morphology/turbo/edit.html.twig', 'delete_edit_color_form');
    }

    // ? --------- delete
    #[Route('/avatar/morphology/delete/{id}', name: 'app_avatar_morphology_delete')]
    public function delete(
        int $id,
        Request $request,
        EntityManagerInterface $entityManagerInterface,
    ) {
        // dd($request->getPayload()->getString('_token'));

        if (!$this->isCsrfTokenValid('delete-morphology', $request->getPayload()->getString('_token'))) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $color = $entityManagerInterface->getRepository(Morphologie::class)->findOneBy(['id' => $id]);
        $entityManagerInterface->remove($color);

        $entityManagerInterface->flush();

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return $this->renderBlock('avatar/morphology/turbo/delete.html.twig', 'delete_morphology', [
            'morphologieId' => $id,
        ]);
    }
}
