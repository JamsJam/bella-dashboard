<?php

namespace App\Controller\Config;

use App\Application\Config\Dto\ClothesConfigDto;
use App\Application\Config\Form\ClothesConfigType;
use App\Application\Config\Service\ClothesConfigService;
use App\Notifier\Services\FlashService;
use App\Service\BreadscrumbsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ClothesConfigController extends AbstractConfigController
{
    #[Route('/config/clothes', name: 'app_config_clothes', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        BreadscrumbsService $breadscrumbs,
        ClothesConfigService $configService,
        FlashService $flashService,
    ): Response {
        $config = $configService->get();
        $form = $this->createForm(ClothesConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ClothesConfigDto $config */
            $config = $form->getData();
            $configService->save($config);
            $flashService->success('Configuration des vêtements mise à jour.');

            return $this->redirectToRoute('app_config_clothes');
        }

        return $this->renderFormPage(
            $request,
            $breadscrumbs,
            'Configuration des vêtements',
            $form->createView(),
            ['clothes_form' => true],
        );
    }
}
