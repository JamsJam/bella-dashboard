<?php

namespace App\UI\Tabs;

use InvalidArgumentException;

final readonly class TabMapper
{
    public function map(
        string $controllerRoute,
        bool $bestsellerOnly = false,
        array $routeParameters = [],
        array $context = [],
    ): TabsDto {
        return match ($controllerRoute) {
            'app_clothes' => $this->mapClothesCatalog($bestsellerOnly),
            'app_clothe_add' => $this->mapClotheCreation(),
            'app_clothes_show' => $this->mapClotheDetails(
                slug: (string) ($routeParameters['slug'] ?? ''),
                hasPublishableVariant: (bool) (
                    $context['hasPublishableVariant'] ?? false
                ),
            ),
            default => throw new InvalidArgumentException(sprintf(
                'Aucune définition d’onglets pour la route "%s".',
                $controllerRoute,
            )),
        };
    }

    private function mapClothesCatalog(bool $bestsellerOnly): TabsDto
    {
        return new TabsDto(
            items: [
                new TabDto(
                    id: 'add',
                    label: 'Ajouter',
                    route: 'app_clothe_add',
                ),
                new TabDto(
                    id: 'add-variant',
                    label: 'Ajouter des variantes',
                    route: 'app_clothes_variant_add',
                ),
                new TabDto(
                    id: 'bestseller',
                    label: 'Bestseller',
                    route: 'app_clothes_bestsellers_modal',
                    isActive: $bestsellerOnly,
                    attributes: $this->turboStreamAttributes(),
                ),
                new TabDto(
                    id: 'featured',
                    label: 'Mises en avant',
                    route: 'app_clothes_featured_modal',
                    attributes: $this->turboStreamAttributes(),
                ),
                new TabDto(
                    id: 'colors',
                    label: 'Gérer les couleurs',
                    route: 'app_clothes_colors_modal',
                    attributes: $this->turboStreamAttributes(),
                ),
                new TabDto(
                    id: 'back',
                    label: 'Retour',
                    route: 'app_dashboard',
                ),
            ],
            ariaLabel: 'Actions du catalogue de vêtements',
        );
    }

    private function mapClotheCreation(): TabsDto
    {
        return new TabsDto(
            items: [
                new TabDto(
                    id: 'back',
                    label: 'Retour',
                    route: 'app_clothes',
                ),
            ],
            ariaLabel: 'Actions de création d’un vêtement',
        );
    }

    private function mapClotheDetails(
        string $slug,
        bool $hasPublishableVariant,
    ): TabsDto {
        $items = [
            new TabDto(
                id: 'back',
                label: 'Retour',
                route: 'app_clothes',
            ),
            new TabDto(
                id: 'edit',
                label: 'Modifier',
                route: 'app_clothes_edit_modal',
                routeParameters: ['slug' => $slug],
                attributes: [
                    'data-turbo-frame' => 'clothe-modal-component',
                ],
            ),
            new TabDto(
                id: 'sizes',
                label: 'Tailles',
                route: 'app_clothes_sizes_modal',
                routeParameters: ['slug' => $slug],
                attributes: $this->turboStreamAttributes(),
            ),
        ];

        if ($hasPublishableVariant) {
            $items[] = new TabDto(
                id: 'schedule',
                label: 'Programmer',
                route: 'app_clothes_schedule_modal',
                routeParameters: ['slug' => $slug],
                attributes: $this->turboStreamAttributes(),
            );
        }

        return new TabsDto(
            items: $items,
            ariaLabel: 'Actions du vêtement',
        );
    }

    /**
     * @return array<string, string>
     */
    private function turboStreamAttributes(): array
    {
        return [
            'data-turbo-stream' => 'true',
        ];
    }
}
