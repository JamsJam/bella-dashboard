<?php

namespace App\Service;

final class BreadscrumbsService
{
    private const ROOT_ROUTE = 'app_dashboard';

    private const ROUTE_PARENTS = [
        'app_clothes_variant_add' => 'app_clothes',
    ];

    private const LABELS = [
        'app' => 'Dashboard',
        'dashboard' => 'Dashboard',
        'user' => 'Utilisateurs',
        'order' => 'Commandes',
        'orders' => 'Commandes',
        'avatar' => 'Avatar',
        'clothes' => 'Vêtements',
        'categories' => 'Catégories',
        'collections' => 'Collections',
        'config' => 'Configuration',
        'page' => 'Shop',
        'contact' => 'Contact',
        'customers' => 'Clients',
        'deliveries' => 'Livraisons',
        'reviews' => 'Avis',
        'show' => 'Détail',
        'pending' => 'En attente',
        'rename' => 'Renommer',
        'index' => 'Index',
        'add' => 'Ajouter',
        'config' => 'Configuration',
    ];

    /**
     * @return array<int, array{label: string, route?: string, params?: array<string, mixed>}>
     */
    public function resolve(string $route, array $routeParams = [], ?string $currentLabel = null): array
    {
        if ('' === trim($route)) {
            throw new \InvalidArgumentException('Route cannot be empty');
        }

        if (!str_starts_with($route, 'app_')) {
            throw new \InvalidArgumentException(sprintf('Route "%s" must start with "app_".', $route));
        }

        if (isset(self::ROUTE_PARENTS[$route])) {
            $breadcrumbs = $this->resolve(self::ROUTE_PARENTS[$route]);
            $lastSegment = ltrim((string) strrchr($route, '_'), '_');
            $breadcrumbs[] = ['label' => $currentLabel ?? (self::LABELS[$lastSegment] ?? ucfirst($lastSegment))];

            return $breadcrumbs;
        }

        $parts = explode('_', $route);

        foreach ($parts as $part) {
            if ('' === $part) {
                throw new \InvalidArgumentException(sprintf('Route "%s" contains an empty segment.', $route));
            }
        }

        $breadcrumbs = [
            [
                'label' => self::LABELS['app'],
                'route' => self::ROOT_ROUTE,
            ],
        ];

        /*
         * app_dashboard => uniquement Dashboard
         */
        if ('app_dashboard' === $route) {
            return $breadcrumbs;
        }

        if ('app_config_index' === $route) {
            $breadcrumbs[] = ['label' => self::LABELS['config']];

            return $breadcrumbs;
        }

        /*
         * On enlève "app" du début.
         */
        array_shift($parts);

        $path = 'app';

        foreach ($parts as $part) {
            /*
             * On ignore "dashboard" pour éviter :
             * Dashboard > Dashboard
             */
            if ('dashboard' === $part) {
                continue;
            }

            $path .= '_' . $part;

            $breadcrumb = [
                'label' => self::LABELS[$part] ?? ucfirst($part),
                'route' => 'app_config' === $path ? 'app_config_index' : $path,
            ];

            if ($path === $route && null !== $currentLabel) {
                $breadcrumb['label'] = $currentLabel;
                unset($breadcrumb['route']);
            } elseif ($path === $route && [] !== $routeParams) {
                $breadcrumb['params'] = $routeParams;
            }

            $breadcrumbs[] = $breadcrumb;
        }

        return $breadcrumbs;
    }
}
