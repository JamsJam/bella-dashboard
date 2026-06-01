<?php

namespace App\UI\ProductGrid;




final readonly class ProductGridViewModel
{
    /**
     * @param list<ProductGridItemModel> $items
     * @param list<ProductGridFilterModel> $filters
     * @param array<string, mixed> $selectedFilters
     */
    public function __construct(
        public string $id,
        public array $items,
        public array $filters,
        public ?string $searchRoute = null,
        public ?string $updateFilterRoute = null,
        public string $title = 'Bibliotheque',
        public string $searchPlaceholder = 'Rechercher...',
        public string $noResultsLabel = 'Aucun resultat trouve',
        public string $detailActionLabel = 'Voir les details',
        public string $detailUrlTemplate = '#',
        public string $deleteUrlTemplate = '#',
        public string $renameUrlTemplate = '#',
        public ?string $deleteCsrfTokenId = null,
        public ?string $renameCsrfTokenId = null,
        public string $resourceKey = 'body',
        public string $resourceParamName = 'partie',
        public array $extraSearchParams = [],
        public bool $showSelectionActions = false,
        public string $selectionCountLabel = 'selectionnee(s)',
        public string $selectAllLabel = 'Tout selectionner',
        public string $clearSelectionLabel = 'Annuler la selection',
        public string $renameActionLabel = 'Renommer',
        public string $deleteActionLabel = 'Supprimer',
        public array $selectionActions = [],
        public string $renameConfirmMessage = 'Replacer __COUNT__ element(s) dans le processus de renommage ?',
        public string $deleteConfirmMessage = 'Supprimer __COUNT__ element(s) ?',
        public string $searchParamName = 'search',
        public ?string $initialSearch = null,
        public array $selectedFilters = [],
        
        public bool $paginate = false,
        public int $itemsPerPage = 20,
        public ?string $paginationParamName = 'page',
        public ?string $paginationNextLabel = 'Next',
        public ?string $paginationPreviousLabel = 'Previous',
        public string $part = 'body',

        


    ) {

        
    }
}
