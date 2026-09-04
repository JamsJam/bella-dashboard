<?php

namespace App\Application\Avatar\Factory\ProductGrid;

use App\Application\Avatar\Mapper\AvatarFilterMapper;
use App\UI\ProductGrid\ProductGridFilterModel;

class AvatarProductGridFilterFactory
{
    public function __construct(
        private readonly AvatarFilterMapper $filterMapper,
    ) {
    }

    /**
     * Crée les filtres du product grid en fonction de la partie d'avatar sélectionnée.
     *
     * @param string $part La partie de l'avatar pour laquelle créer les filtres (body, face, eyebrows, etc.)
     *
     * @return ProductGridFilterModel[] tableau de ProductGridFilterModel à utiliser dans le product grid
     *
     * @throws \InvalidArgumentException Si la partie d'avatar n'est pas fournie ou invalide
     */
    public function createAvatarProductFiltersbyPart(string $part): array|\InvalidArgumentException
    {
        if (!$part) {
            throw new \InvalidArgumentException("La partie d'avatar est requise pour créer les filtres du product grid.");
        }

        $filters = $this->filterMapper->getFiltersForPart($part);
        // dd($filters);
        /*
            $filter[
                'id' strint
                'label' string,
                'options' array [
                    ['value' => 'red', 'label' => 'Rouge'],
                    ['value' => 'blue', 'label' => 'Bleu'],
                    ['value' => 'green', 'label' => 'Vert'],
                ]
            ]
         */

        return array_map(function ($filter) {
            return new ProductGridFilterModel(
                id: $filter['id'],
                label: $filter['label'],
                options: $filter['options'],
                selected: $filter['selected'] ?? null,
                allowCreate: $filter['allowCreate'] ?? false,
                isColor: $filter['isColor'] ?? false,
            );
        }, $filters);
    }
}
