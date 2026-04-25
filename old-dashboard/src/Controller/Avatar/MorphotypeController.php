<?php

namespace App\Controller\Avatar;

use Symfony\UX\Turbo\TurboBundle;
use App\Form\Avatar\MorphotypeForm;
use App\Entity\Avatar\Body\Morphotype;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Provider\PageMetadata\PageMetadataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class MorphotypeController extends AbstractController
{
// ? --------- index
    #[Route('/avatar/morphotype', name: 'app_avatar_morphotype')]
    public function index(
        PageMetadataProvider $pageMetadata,
        Request $request,
        EntityManagerInterface $entityManagerInterface,
    ): Response {
        $metaData = $pageMetadata->getPageMetada($request->attributes->get('_route'));
        $morphotypes = $entityManagerInterface->getRepository(Morphotype::class)->findAll();

        return $this->render('avatar/morphotype/index.html.twig', [
            'metaData' => $metaData,
            'morphotypes' => $morphotypes,
        ]);
    }

// ? --------- new
    #[Route('/avatar/morphotype/new', name: 'app_avatar_morphotype_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManagerInterface,
    ): Response {
        $form = $this->createForm(MorphotypeForm::class, new Morphotype(), [
            'action' => $this->generateUrl('app_avatar_morphotype_new'),
        ]);

        $form->handleRequest($request);

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        if ($form->isSubmitted() && $form->isValid()) {
            $morphotype = $form->getData();

            $newMorphotype = new Morphotype();
            $today = new \DateTimeImmutable();
            $newMorphotype
                ->setName(strtolower($morphotype->getName()))
                ->setCreatedAt($today)
                ->setEditedAt($today)
            ;
            $entityManagerInterface->persist($newMorphotype);
            $entityManagerInterface->flush();

            $this->addFlash(
                'add-success',
                'La morphotype "'.$newMorphotype->getName().'" à bien été ajouté'
            );

            return $this->renderBlock('avatar/morphotype/turbo/new.html.twig', 'success_add_morphotype', [
                'morphotype' => $newMorphotype,
            ]);
        }

        return $this->renderBlock('avatar/morphotype/turbo/new.html.twig', 'new_morphotype_form', [
            'form' => $form,
        ]);
    }

    #[Route('/avatar/morphotype/cancel/new', name: 'app_avatar_morphotype_new_cancel', methods: ['GET'])]
    public function cancel_new(
        Request $request,
    ) {
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return $this->renderBlock('avatar/morphotype/turbo/new.html.twig', 'delete_new_morphotype_form');
    }

// ? --------- edit
    #[Route('/avatar/morphotype/edit/{id}', name: 'app_avatar_morphotype_edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $entityManagerInterface,
    ): Response {
        $morphotype = $entityManagerInterface->getRepository(Morphotype::class)->findOneBy(['id' => $id]);

        $form = $this->createForm(MorphotypeForm::class, $morphotype);
        $form->handleRequest($request);

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        if ($form->isSubmitted() && $form->isValid()) {
            $today = new \DateTimeImmutable();

            $morphotype
                ->setEditedAt($today)
                ->setName(strtolower($form->getData()->getName()))
            ;
            $entityManagerInterface->persist($morphotype);
            $entityManagerInterface->flush();

            return $this->renderBlock('avatar/morphotype/turbo/edit.html.twig', 'success_edit_morphotype', [
                'morphotype' => $morphotype,
                'id' => $id,
            ]);
        }

        return $this->renderBlock('avatar/morphotype/turbo/edit.html.twig', 'edit_morphotype_form', [
            'morphotype' => $morphotype,
            'id' => $id,
            'form' => $form,
        ]);
    }

    #[Route('/avatar/morphotype/cancel/edit', name: 'app_avatar_morphotype_edit_cancel', methods: ['GET'])]
    public function cancel_edit(
        Request $request,
    ): Response {
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return $this->renderBlock('avatar/morphotype/turbo/edit.html.twig', 'delete_edit_color_form');
    }

// ? --------- delete
    #[Route('/avatar/morphotype/delete/{id}', name: 'app_avatar_morphotype_delete')]
    public function delete(
        int $id,
        Request $request,
        EntityManagerInterface $entityManagerInterface,
    ) {
        // dd($request->getPayload()->getString('_token'));

        if (!$this->isCsrfTokenValid('delete-morphotype', $request->getPayload()->getString('_token'))) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $color = $entityManagerInterface->getRepository(Morphotype::class)->findOneBy(['id' => $id]);
        $entityManagerInterface->remove($color);

        $entityManagerInterface->flush();

        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        return $this->renderBlock('avatar/morphotype/turbo/delete.html.twig', 'delete_morphotype', [
            'morphotypeId' => $id,
        ]);
    }
}

