<?php

namespace App\UI\ProductGrid;

class ProductGridItemModel
{
    public function __construct(
        public string $id,
        public string $name,
        public string $imageUrl,
        public array $imageUrls = [],
        public array $attributes = [],
        public ?string $slug = null,
        public ?bool $isOnline = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'imageUrl' => $this->imageUrl,
            'imageUrls' => $this->imageUrls,
            'attributes' => $this->attributes,
            'slug' => $this->slug,
            'isOnline' => $this->isOnline,
        ];
    }
}
