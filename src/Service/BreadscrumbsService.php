<?php

namespace App\Service;

class BreadscrumbsService
{
    private const LABELS = [
        'dashboard' => 'Dashboard',
        'user' => 'Utilisateurs',
        'order' => 'Commandes',
        'pending' => 'En attente',
    ];

    /**
     * Transforme un nom de route Symfony en breadcrumb hiérarchique.
     *
     * Exemple :
     *  app_user_index → [
     *      ['label' => 'Utilisateurs', 'route' => 'app_user'],
     *      ['label' => 'Index', 'route' => 'app_user_index'],
     *  ]
     *
     * Règles :
     * - La route doit commencer par "app_"
     * - Chaque segment est séparé par "_"
     * - Les segments sont convertis en labels via LABELS ou ucfirst()
     *
     * Exceptions :
     * - Lance une InvalidArgumentException si :
     *   - la route est vide
     *   - un segment est vide (ex: "app_user__index")
     *
     * @param string $route Nom de la route Symfony
     * @return array<int, array{label: string, route: string}>
     *
     * @throws \InvalidArgumentException
     */
    public function resolve(string $route): array
    {
        if (trim($route) === '') {
            throw new \InvalidArgumentException('Route cannot be empty');
        }
        $parts = explode('_', str_replace('app_', '', $route));

        $breadcrumbs = [];

        $path = 'app';

        foreach ($parts as $part) {
            if ($part === '') {
                throw new \InvalidArgumentException(
                    sprintf('Route "%s" contains an empty segment.', $route)
                );
            }
            $path .= '_' . $part;

            $breadcrumbs[] = [
                'label' => self::LABELS[$part] ?? ucfirst($part),
                'route' => $path,
            ];
        }

        return $breadcrumbs;
    }
}
