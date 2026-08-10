<?php

namespace App\UI\Tabs;

final readonly class TabDto
{
    /**
     * @param array<string, string> $attributes
     * @param array<string, mixed>  $routeParameters
     */
    public function __construct(
        public string $id,
        public string $label,
        public string $route,
        public array $routeParameters = [],
        public bool $isActive = false,
        public array $attributes = [],
    ) {
    }
}
