<?php

namespace App\Provider\PageMetadata;

use App\Builder\PageMeta\PageMetaBuilder;
use App\DTO\PageMetadata\PageMetadataDTO;

final class PageMetadataProvider
{
    public function __construct(
        private PageMetaBuilder $pageMetaBuilder,
    ) {
    }

    /**
     * Retourne les metadata de la page.
     */
    public function getPageMetada(string $currentroute): PageMetadataDTO
    {
        $metaData = $this->pageMetaBuilder->buildPageMetaData($currentroute);

        return $metaData;
    }
}
