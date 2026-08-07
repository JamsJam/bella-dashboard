<?php

namespace App\Application\Avatar\Services;

use App\Application\Avatar\Factory\ProductGrid\AvatarProductGridViewFactory;
use App\UI\ProductGrid\ProductGridFilterModel;
use App\UI\ProductGrid\ProductGridViewModel;

final class AvatarProductGridService
{
    public function __construct(
        private readonly AvatarProductGridViewFactory $avatarProductGridViewFactory,
    ) {
    }

    public function createProductGridView(
        string $part = 'body',
        array $selectedFilters = [],
        string $id = 'avatar-grid',
    ): ProductGridViewModel {
        return $this->avatarProductGridViewFactory->create(
            id: $id,
            selectedFilters: $selectedFilters,
            part: $part
        );
    }

    /**
     * Retourne la liste des filtres disponibles pour la partie d'avatar sélectionnée.
     *
     * @return ProductGridFilterModel[]
     */
    public function getFiltersForPart(string $part = 'body'): array
    {
        return $this->avatarProductGridViewFactory->create(
            id: 'avatar-grid',
            selectedFilters: [],
            part: $part
        )->filters;
    }

    // public function getFiltersByPart($part): array
    // {
    //     $filter = $this->avatarProductGridViewFactory->create(
    //         id: 'avatar-grid',
    //         selectedFilters: [],
    //         part: $part
    //     )->filters;

    //     return $filters;
    // }
}
