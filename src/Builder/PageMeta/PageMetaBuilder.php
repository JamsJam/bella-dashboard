<?php

namespace App\Builder\PageMeta;

use App\DTO\Breadcrumb\BreadcrumbItemDTO;
use App\DTO\PageMetadata\PageMetadataDTO;
use App\Validator\DtoValidator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Yaml\Yaml;

class PageMetaBuilder
{
    public function __construct(
        private string $pageMetaPath,
        private DtoValidator $dtoValidator,
    ) {
    }

    public function buildPageMetaData(string $route): PageMetadataDTO
    {
        $parsedMetaData = (array) Yaml::parseFile($this->pageMetaPath);
        // dd($parsedMetaData["dashboard_content"][$route][0]);
        $metaData = $this->createPageMetaDataFromDTO($parsedMetaData['dashboard_content'][$route][0]);

        return $metaData;
    }

    /**
     * @throws BadRequestHttpException
     */
    private function createPageMetaDataFromDTO(array $pageMetadata): PageMetadataDTO
    {
        $dto = (new PageMetadataDTO())
            ->setTitle($pageMetadata['title'])
            ->setRouteKey($pageMetadata['routeKey'])
            ->setBreadcrumb(array_map([$this, 'createBreadscrumbFromDTO'], $pageMetadata['breadcrumb']));

        $this->dtoValidator->validate($dto);

        return $dto;
    }

    /**
     * @throws BadRequestHttpException
     */
    private function createBreadscrumbFromDTO(array $breadcrumItem): BreadcrumbItemDTO
    {
        $dto = (new BreadcrumbItemDTO())
            ->setTitle($breadcrumItem['title'])
            ->setRoute($breadcrumItem['route'])
        ;

        $this->dtoValidator->validate($dto);

        return $dto;
    }
}
