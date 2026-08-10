<?php

namespace App\Controller\Clothes\Clothe\Create;

use App\Application\Clothes\DTO\ClotheFormInput;
use App\Application\Clothes\DTO\VariantGroupInput;
use App\Application\Clothes\Exception\DuplicateClotheVariantException;
use App\Application\Clothes\Form\ClotheType;
use App\Application\Clothes\Services\Clothe\ClothesCreationService;
use App\Notifier\Services\FlashService;
use App\Service\BreadscrumbsService;
use App\Service\LoggerService;
use App\UI\Tabs\TabsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AddClotheController extends AbstractController
{
    #[Route('/clothes/add', name: 'app_clothe_add', methods: ['GET', 'POST'], priority: 20)]
    public function add(
        Request $request,
        ClothesCreationService $creationService,
        BreadscrumbsService $breadscrumbsService,
        TabsService $tabsService,
        FlashService $flashService,
        LoggerService $logger,
    ): Response {
        $input = new ClotheFormInput();
        $input->variants[] = new VariantGroupInput();
        $form = $this->createForm(ClotheType::class, $input);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $clothe = $creationService->create($input);
                $status = $clothe->getVariants()
                    ->first()
                    ->getPublicationStatus()
                    ->label();
                $flashService->success(sprintf(
                    'Vêtement créé avec le statut %s.',
                    $status,
                ));
                $logger->info('Clothe created.', [
                    'clothe_id' => $clothe->getId(),
                ]);
            } catch (
                DuplicateClotheVariantException | \InvalidArgumentException $exception
            ) {
                $flashService->error($exception->getMessage());
                $logger->warning('Clothe creation rejected.', [
                    'error' => $exception->getMessage(),
                ]);

                return $this->redirectToRoute('app_clothe_add');
            }

            return $this->redirectToRoute('app_clothes');
        }

        return $this->render('clothes/add.html.twig', [
            'breadscrumbs' => $breadscrumbsService->resolve(
                (string) $request->attributes->get('_route'),
            ),
            'tabs' => $tabsService->create(),
            'form' => $form,
        ]);
    }
}
