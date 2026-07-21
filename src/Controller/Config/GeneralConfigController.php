<?php

namespace App\Controller\Config;

use App\Application\Config\Dto\GeneralConfigDto;
use App\Application\Config\Form\GeneralConfigType;
use App\Application\Config\Service\GeneralConfigService;
use App\Notifier\Services\FlashService;
use App\Service\BreadscrumbsService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GeneralConfigController extends AbstractConfigController
{
    #[Route('/config/general', name: 'app_config_general', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        BreadscrumbsService $breadscrumbs,
        GeneralConfigService $generalConfigService,
        FlashService $flashService,
    ): Response {
        /** @var GeneralConfigDto $config */
        $config = $generalConfigService->get();

        $form = $this->createForm(GeneralConfigType::class, $config);

        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            /** @var GeneralConfigDto $config */
            $config = $form->getData();
            $logoFile = $form->get('logoFile')->getData();
            $faviconFile = $form->get('faviconFile')->getData();

            $generalConfigService->save(
                $config,
                $logoFile instanceof UploadedFile ? $logoFile : null,
                $faviconFile instanceof UploadedFile ? $faviconFile : null,
            );
            $flashService->success('Configuration générale mise à jour.');

            return $this->redirectToRoute('app_config_general');
        }

        return $this->renderFormPage($request, $breadscrumbs, 'Configuration générale', $form->createView(), [
            'subtitle' => 'Titre du site, logo et favicon.',
            'back_url' => $this->generateUrl('app_config_index'),
            'back_label' => 'Retour aux configurations',
        ]);
    }
}
