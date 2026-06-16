<?php

namespace App\Controller\Config;

use App\Application\Config\Dto\ContactConfigDto;
use App\Application\Config\Form\ContactConfigType;
use App\Application\Config\Service\ContactConfigService;
use App\Notifier\Services\FlashService;
use App\Service\BreadscrumbsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactConfigController extends AbstractConfigController
{
    #[Route('/config/contact', name: 'app_config_contact', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        BreadscrumbsService $breadscrumbs,
        ContactConfigService $configService,
        FlashService $flashService,
    ): Response {
        $config = $configService->get();
        $form = $this->createForm(ContactConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ContactConfigDto $config */
            $config = $form->getData();
            $configService->save($config);
            $flashService->success('Configuration de contact mise à jour.');

            return $this->redirectToRoute('app_config_contact');
        }

        return $this->renderFormPage($request, $breadscrumbs, 'Configuration de contact', $form->createView());
    }
}
