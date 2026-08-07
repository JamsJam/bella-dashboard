<?php

namespace App\Application\PageGenerator\Services;

use App\Application\PageGenerator\Page\Page;
use App\Application\PageGenerator\Page\PageBuilder;
use App\Service\YamlLoaderService;

final class PageService
{
    public function __construct(
        private PageBuilder $pageBuilder,
        private YamlLoaderService $yamlLoaderService,
    ) {
    }

    public function createPageFromYamlFile(string $filePath, array $params = []): Page
    {
        // $yamlData = $this->yamlLoaderService->load($filePath); //yamlService

        $page = $this->pageBuilder
            ->fromYaml($this->yamlLoaderService->load($filePath), $params)
            ->build();

        return $page;
    }
}
