<?php

namespace App\DTO\Breadcrumb;

final class BreadcrumbItemDTO
{
    private ?string $title = null;
    private ?string $route = null;

    /**
     * Get the value of title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the value of title.
     */
    public function setTitle($title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of route.
     */
    public function getRoute(): string
    {
        return $this->route;
    }

    /**
     * Set the value of route.
     */
    public function setRoute($route): self
    {
        $this->route = $route;

        return $this;
    }
}
