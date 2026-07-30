<?php

namespace App\Service;

final class BreadscrumbsService
{
    private const ROOT_ROUTE = 'app_dashboard';

    private const LABELS = [
        'app' => 'Dashboard',
        'dashboard' => 'Dashboard',
        'user' => 'Utilisateurs',
        'order' => 'Commandes',
        'orders' => 'Commandes',
        'avatar'=>'Avatar',
        'clothes' => 'Vêtements',
        'categories' => 'Catégories',
        'collections' => 'Collections',
        'config' => 'Configuration',
        'page' => 'Shop',
        'contact' => 'Contact',
        'customers' => 'Clients',
        'deliveries' => 'Livraisons',
        'show' => 'Détail',
        'pending' => 'En attente',
        'rename' => 'Renommer',
        'index' => 'Index',
        'add' => 'Ajouter',
        "config" => "Configuration",
    ];

    /**
     * @return array<int, array{label: string, route?: string, params?: array<string, mixed>}>
     */
    public function resolve(string $route, array $routeParams = [], ?string $currentLabel = null): array
    {
        if (trim($route) === '') {
            throw new \InvalidArgumentException('Route cannot be empty');
        }

        if (!str_starts_with($route, 'app_')) {
            throw new \InvalidArgumentException(
                sprintf('Route "%s" must start with "app_".', $route)
            );
        }

        $parts = explode('_', $route);

        foreach ($parts as $part) {
            if ($part === '') {
                throw new \InvalidArgumentException(
                    sprintf('Route "%s" contains an empty segment.', $route)
                );
            }
        }

        $breadcrumbs = [
            [
                'label' => self::LABELS['app'],
                'route' => self::ROOT_ROUTE,
            ],
        ];

        /**
         * app_dashboard => uniquement Dashboard
         */
        if ($route === 'app_dashboard') {
            return $breadcrumbs;
        }

        if ($route === 'app_config_index') {
            $breadcrumbs[] = ['label' => self::LABELS['config']];

            return $breadcrumbs;
        }

        /**
         * On enlève "app" du début.
         */
        array_shift($parts);

        $path = 'app';

        foreach ($parts as $part) {
            /**
             * On ignore "dashboard" pour éviter :
             * Dashboard > Dashboard
             */
            if ($part === 'dashboard') {
                continue;
            }

            $path .= '_' . $part;

            $breadcrumb = [
                'label' => self::LABELS[$part] ?? ucfirst($part),
                'route' => $path === 'app_config' ? 'app_config_index' : $path,
            ];

            if ($path === $route && $currentLabel !== null) {
                $breadcrumb['label'] = $currentLabel;
                unset($breadcrumb['route']);
            } elseif ($path === $route && $routeParams !== []) {
                $breadcrumb['params'] = $routeParams;
            }

            $breadcrumbs[] = $breadcrumb;
        }

        return $breadcrumbs;
    }
}
