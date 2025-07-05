<?php

namespace App\DTO\PageMetadata;

use App\DTO\Breadcrumb\BreadcrumbItemDTO;

final class PageMetadataDTO
{
    private ?string $title = null;

    private ?string $routeKey = null;

    /**
     * @var breadcrumbDTO[]
     */
    private array $breadcrumb = [];

    /**
     * Get the value of routeKey.
     */
    public function getRouteKey()
    {
        return $this->routeKey;
    }

    /**
     * Set the value of routeKey.
     *
     * @return self
     */
    public function setRouteKey($routeKey)
    {
        $this->routeKey = $routeKey;

        return $this;
    }

    /**
     * Get the value of title.
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the value of title.
     *
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of breadcrumb.
     *
     * @return BreadcrumbItemDTO[]
     */
    public function getBreadcrumb()
    {
        return $this->breadcrumb;
    }

    /**
     * Set the value of breadcrumb.
     *
     * @param breadcrumbDTO[] $breadcrumb
     *
     * @return self
     */
    public function setBreadcrumb(array $breadcrumb)
    {
        $this->breadcrumb = $breadcrumb;

        return $this;
    }
}
