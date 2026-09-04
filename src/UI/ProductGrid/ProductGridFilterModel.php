<?php

namespace App\UI\ProductGrid;

class ProductGridFilterModel
{
    public function __construct(
        public string $id,
        public string $label,
        public array $options = [],
        public ?string $selected = null,
        public bool $allowCreate = false,
        public bool $isColor = false,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'options' => $this->options,
            'selected' => $this->selected,
            'allowCreate' => $this->allowCreate,
            'isColor' => $this->isColor,
        ];
    }
}
