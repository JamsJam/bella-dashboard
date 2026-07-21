<?php

namespace App\Controller\Config;

use App\Service\BreadscrumbsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ConfigController extends AbstractController
{
    #[Route('/config', name: 'app_config_index', methods: ['GET'])]
    public function __invoke(Request $request, BreadscrumbsService $breadscrumbs): Response
    {
        return $this->render('config/index.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve((string) $request->attributes->get('_route')),
            'configurations' => [
                [
                    'route' => 'app_config_general',
                    'icon' => 'ep:setting',
                    'title' => 'Général',
                    'description' => 'Titre du site, logo et favicon.',
                ],
                [
                    'route' => 'app_config_pages',
                    'icon' => 'ep:shop',
                    'title' => 'Shop',
                    'description' => 'Contenus et métadonnées des pages de la boutique.',
                ],
                [
                    'route' => 'app_config_orders',
                    'icon' => 'ep:document',
                    'title' => 'Commandes',
                    'description' => 'TVA, destinations et frais de livraison.',
                ],
                [
                    'route' => 'app_config_clothes',
                    'icon' => 'ep:goods',
                    'title' => 'Vêtements',
                    'description' => 'Paramètres appliqués au catalogue de vêtements.',
                ],
                [
                    'route' => 'app_config_contact',
                    'icon' => 'ep:user',
                    'title' => 'Contact',
                    'description' => 'Coordonnées du propriétaire et contact développeur.',
                ],
            ],
        ]);
    }
}
