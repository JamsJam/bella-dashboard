<?php

namespace App\Controller\Config;

use App\Application\Config\ConfigModel\GeneralConfigModel;
use App\Application\Config\ConfigModel\OrderConfigModel;
use App\Application\Config\Provider\ApplicationConfigProvider;
use App\Application\Config\Service\ConfigurationYamlService;
use App\Notifier\Services\FlashService;
use App\Service\BreadscrumbsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ConfigController extends AbstractController
{
    #[Route('/config', name: 'app_config', methods: ['GET'])]
    public function index(
        Request $request,
        BreadscrumbsService $breadscrumbs,
        ApplicationConfigProvider $configProvider,
    ): Response {
        return $this->render('config/index.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve((string) $request->attributes->get('_route')),
            'general' => $configProvider->getGeneralConfig(),
            'order' => $configProvider->getOrderConfig(),
        ]);
    }

    #[Route('/config/general', name: 'app_config_general_update', methods: ['POST'])]
    public function updateGeneral(
        Request $request,
        ConfigurationYamlService $configurationYamlService,
        FlashService $flashService,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('config_general_update', (string) $request->request->get('_csrf_token'))) {
            $flashService->error('Token CSRF invalide.');

            return $this->redirectToRoute('app_config');
        }

        $general = GeneralConfigModel::fromArray([
            'email' => $request->request->get('email'),
            'address' => $request->request->get('address'),
            'phone' => $request->request->get('phone'),
            'site_title' => $request->request->get('site_title'),
            'site_description' => $request->request->get('site_description'),
        ]);

        $configurationYamlService->saveSection('general', 'general', $general->toArray());
        $flashService->success('Configuration generale mise a jour.');

        return $this->redirectToRoute('app_config');
    }

    #[Route('/config/order', name: 'app_config_order_update', methods: ['POST'])]
    public function updateOrder(
        Request $request,
        ConfigurationYamlService $configurationYamlService,
        FlashService $flashService,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('config_order_update', (string) $request->request->get('_csrf_token'))) {
            $flashService->error('Token CSRF invalide.');

            return $this->redirectToRoute('app_config');
        }

        $freeShippingFrom = trim((string) $request->request->get('free_shipping_from', ''));
        $order = OrderConfigModel::fromArray([
            'currency' => $request->request->get('currency'),
            'default_status' => $request->request->get('default_status'),
            'minimum_amount' => $request->request->get('minimum_amount'),
            'shipping_cost' => $request->request->get('shipping_cost'),
            'free_shipping_from' => $freeShippingFrom !== '' ? $freeShippingFrom : null,
            'allow_guest_checkout' => $request->request->getBoolean('allow_guest_checkout'),
        ]);

        $configurationYamlService->saveSection('order', 'order', $order->toArray());
        $flashService->success('Configuration commande mise a jour.');

        return $this->redirectToRoute('app_config');
    }
}
