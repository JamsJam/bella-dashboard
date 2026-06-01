<?php

namespace App\Application\Avatar\Factory\ProductGrid;

use App\UI\ProductGrid\ProductGridFilterModel;
use App\UI\ProductGrid\ProductGridItemModel;
use App\UI\ProductGrid\ProductGridViewModel;
use App\Application\Avatar\Mapper\AvatarFilterMapper;
use App\Application\Avatar\Factory\ProductGrid\AvatarProductGridFilterFactory;
use App\Application\Avatar\Factory\ProductGrid\AvatarProductGridItemsFactory;

class AvatarProductGridViewFactory
{

    public function __construct(
        private readonly AvatarFilterMapper $filterMapper,
        private readonly AvatarProductGridFilterFactory $avatarProductGridFilterFactory,
        private readonly AvatarProductGridItemsFactory $avatarProductGridItemsFactory
    )
    {}

    /**
     * Retourne les données nécessaires pour afficher le product grid d'avatar en fonction de la partie d'avatar sélectionnée et des filtres appliqués
     *
     * @param string $id
     * @param ProductGridFilterModel[] $filters
     * @param string|null $initialSearch
     * @param array $selectedFilters

     * @return ProductGridViewModel
     */
    public function create(
        ?string $id = 'avatar-grid',
        // ?string $initialSearch = null,
        array $selectedFilters = [],
        string $part = 'body'
    ): ProductGridViewModel
    {
        

        return new ProductGridViewModel(
            id: $id,
            filters: $this->getFiltersList($part),
            items: $this->getProductGridItems($part, $selectedFilters),
            searchRoute: 'app_search_avatar',
            updateFilterRoute: 'app_search_avatar_filters',
            title: 'Bibliotheque d\'avatars',
            searchPlaceholder: 'Rechercher un avatar...',
            detailUrlTemplate: '/avatar/__PART__/__ID__',
            deleteUrlTemplate: '/avatar/__PART__/__ID__',
            renameUrlTemplate: '/avatar/__PART__/__ID__/rename',
            deleteCsrfTokenId: 'avatar_part_delete',
            renameCsrfTokenId: 'avatar_part_queue_rename',
            resourceKey: $part,
            resourceParamName: 'partie',
            showSelectionActions: true,
            renameConfirmMessage: 'Replacer __COUNT__ piece(s) dans le processus de renommage ?',
            deleteConfirmMessage: 'Supprimer __COUNT__ piece(s) d avatar ?',
            searchParamName: 'search',
            paginate: false,
            part: $part,
        );
    }

    /**
     * retourne la liste des filtres à afficher dans le product grid en fonction de la partie d'avatar sélectionnée
     *
     * @param string $part
     * @return ProductGridFilterModel[]
     */
    private function getFiltersList(string $part):array
    {
        return $this->avatarProductGridFilterFactory->createAvatarProductFiltersbyPart($part);
    }


    /**
     * Retourne la liste des items du product grid à afficher en fonction de la partie d'avatar sélectionnée et des filtres appliqués
     *
     * @param string $avatarParts
     * @param array $selectedFilters
     * @return ProductGridItemModel[]
     */
    private function getProductGridItems(string $avatarParts, array $selectedFilters): array
    {

        return $this->avatarProductGridItemsFactory->createAvatarProductItemssbyPart(
            part : $avatarParts, 
            filters : $selectedFilters
        );

    }


}
