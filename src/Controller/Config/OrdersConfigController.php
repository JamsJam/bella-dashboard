<?php

namespace App\Controller\Config;

use App\Application\Config\Dto\OrdersConfigDto;
use App\Application\Config\Form\OrdersConfigType;
use App\Application\Config\Service\OrdersConfigService;
use App\Notifier\Services\FlashService;
use App\Service\BreadscrumbsService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrdersConfigController extends AbstractConfigController
{
    #[Route('/config/orders', name: 'app_config_orders', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        BreadscrumbsService $breadscrumbs,
        OrdersConfigService $configService,
        FlashService $flashService,
    ): Response {
        $config = $configService->get();
        $form = $this->createForm(OrdersConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var OrdersConfigDto $config */
            $config = $form->getData();
            $configService->save($config, $this->getShippingFlagFiles($form->get('shippingFees')));
            $flashService->success('Configuration des commandes mise à jour.');

            return $this->redirectToRoute('app_config_orders');
        }

        return $this->renderFormPage(
            $request,
            $breadscrumbs,
            'Configuration des commandes',
            $form->createView(),
            ['orders_form' => true],
        );
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function getShippingFlagFiles(iterable $shippingFeesForm): array
    {
        $flagFiles = [];

        foreach ($shippingFeesForm as $index => $shippingFeeForm) {
            if (!$shippingFeeForm->has('flagFile')) {
                continue;
            }

            $flagFile = $shippingFeeForm->get('flagFile')->getData();
            if ($flagFile instanceof UploadedFile) {
                $flagFiles[(int) $index] = $flagFile;
            }
        }

        return $flagFiles;
    }
}
