<?php

namespace App\Application\Avatar\Mapper;

use App\Entity\Avatar\Body\Morphologie;
use App\Entity\Avatar\Body\Morphotype;
use App\Entity\Avatar\Eyebrows\Eyebrowscolor;
use App\Entity\Avatar\Eyebrows\Eyebrowshape;
use App\Entity\Avatar\Eyes\Eyecolor;
use App\Entity\Avatar\Eyes\Eyeshape;
use App\Entity\Avatar\Faces\Faceshape;
use App\Entity\Avatar\Hairs\Hairscolor;
use App\Entity\Avatar\Hairs\Hairshape;
use App\Entity\Avatar\Mouths\Mouthscolor;
use App\Entity\Avatar\Mouths\Mouthshape;
use App\Entity\Avatar\Noses\Noseshape;
use App\Entity\Avatar\Skincolor;
use App\Entity\Clothes\Clothes;
use App\Entity\Collections\Collections;
use Doctrine\ORM\EntityManagerInterface;

final class AvatarFilterMapper
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    private const PART_LABELS = [
        'body' => 'Corps',
        'face' => 'Visage',
        'eyebrows' => 'Sourcils',
        'eyes' => 'Yeux',
        'hair' => 'Cheveux',
        'mouth' => 'Bouche',
        'nose' => 'Nez',
    ];

    private const FILTERS_BY_PART = [
        'body' => [
            ['id' => 'skinColor', 'label' => 'Couleur de peau', 'source' => Skincolor::class, 'emptyLabel' => 'Toutes'],
            ['id' => 'morphologie', 'label' => 'Morphologie', 'source' => Morphologie::class, 'emptyLabel' => 'Toutes', 'allowCreate' => false],
            ['id' => 'morphotype', 'label' => 'Morphotype', 'source' => Morphotype::class, 'emptyLabel' => 'Tous', 'allowCreate' => false],
            ['id' => 'clothes', 'label' => 'Vetement', 'source' => Clothes::class, 'emptyLabel' => 'Tous', 'allowCreate' => false],
            ['id' => 'collection', 'label' => 'Collection', 'source' => Collections::class, 'emptyLabel' => 'Toutes', 'allowCreate' => false],
        ],
        'face' => [
            ['id' => 'skinColor', 'label' => 'Couleur de peau', 'source' => Skincolor::class, 'emptyLabel' => 'Toutes'],
            ['id' => 'shape', 'label' => 'Forme', 'source' => Faceshape::class, 'emptyLabel' => 'Toutes'],
        ],
        'eyebrows' => [
            ['id' => 'color', 'label' => 'Couleur', 'source' => Eyebrowscolor::class, 'emptyLabel' => 'Toutes'],
            ['id' => 'shape', 'label' => 'Forme', 'source' => Eyebrowshape::class, 'emptyLabel' => 'Toutes'],
        ],
        'eyes' => [
            ['id' => 'color', 'label' => 'Couleur', 'source' => Eyecolor::class, 'emptyLabel' => 'Toutes'],
            ['id' => 'shape', 'label' => 'Forme', 'source' => Eyeshape::class, 'emptyLabel' => 'Toutes'],
        ],
        'hair' => [
            ['id' => 'color', 'label' => 'Couleur', 'source' => Hairscolor::class, 'emptyLabel' => 'Toutes'],
            ['id' => 'shape', 'label' => 'Forme', 'source' => Hairshape::class, 'emptyLabel' => 'Toutes'],
            ['id' => 'side', 'label' => 'Cote', 'options' => [
                ['value' => 'front', 'label' => 'Front'],
                ['value' => 'back', 'label' => 'Back'],
            ], 'allowCreate' => false],
        ],
        'mouth' => [
            ['id' => 'color', 'label' => 'Couleur', 'source' => Mouthscolor::class, 'emptyLabel' => 'Toutes'],
            ['id' => 'shape', 'label' => 'Forme', 'source' => Mouthshape::class, 'emptyLabel' => 'Toutes'],
        ],
        'nose' => [
            ['id' => 'skinColor', 'label' => 'Couleur de peau', 'source' => Skincolor::class, 'emptyLabel' => 'Toutes'],
            ['id' => 'shape', 'label' => 'Forme', 'source' => Noseshape::class, 'emptyLabel' => 'Toutes'],
        ],
    ];

    /**
     * Récupère les filtres disponibles pour une partie d'avatar donnée
     *
     * @param string $part La partie de l'avatar (body, face, eyebrows, etc.)
     * @return array Un tableau de filtres avec leurs options
     */
    public function getFiltersForPart(string $part = 'body'): array
    {
        $part = $this->normalizePart($part);
        $filters = [$this->createPartFilter($part)];

        foreach (self::FILTERS_BY_PART[$part] as $filterDefinition) {
            $filters[] = [
                'id' => $filterDefinition['id'],
                'label' => $filterDefinition['label'],
                'options' => $filterDefinition['options'] ?? $this->createOptions($filterDefinition['source'], $filterDefinition['emptyLabel']),
                'selected' => '',
                'allowCreate' => $filterDefinition['allowCreate'] ?? true,
            ];
        }

        return $filters;
    }

    /**
     * Récupère les filtres disponibles pour toutes les parties d'avatar
     *
     * @return array Un tableau associatif où la clé est la partie de l'avatar et la valeur est un tableau de filtres
     */
    public function getFiltersByPart(): array
    {
        $filtersByPart = [];

        foreach (array_keys(self::FILTERS_BY_PART) as $part) {
            $filtersByPart[$part] = $this->getFiltersForPart($part);
        }

        return $filtersByPart;
    }

    public function getFilterSourceClass(string $part, string $filterId): ?string
    {
        $part = $this->normalizePart($part);

        foreach (self::FILTERS_BY_PART[$part] as $filterDefinition) {
            if ($filterDefinition['id'] === $filterId) {
                return $filterDefinition['source'] ?? null;
            }
        }

        return null;
    }

    public function getPartLabels(): array
    {
        return self::PART_LABELS;
    }

    /**
     * Normalise la partie d'avatar pour s'assurer qu'elle correspond à une clé valide dans FILTERS_BY_PART
     */
    private function normalizePart(string $part): string
    {
        $part = strtolower($part);

        return isset(self::FILTERS_BY_PART[$part]) ? $part : 'body';
    }

    private function createPartFilter(string $selectedPart): array
    {
        $options = [];

        foreach (self::PART_LABELS as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return [
            'id' => 'partie',
            'label' => 'Partie',
            'options' => $options,
            'selected' => $selectedPart,
        ];
    }

    private function createOptions(string $entityClass, string $emptyLabel): array
    {
        $options = [['value' => '', 'label' => $emptyLabel]];
        $entities = $this->entityManager->getRepository($entityClass)->findBy([], ['id' => 'ASC']);

        foreach ($entities as $entity) {
            $id = $entity->getId();
            $label = $this->resolveLabel($entity);

            if ($id !== null && $label !== null && $label !== '') {
                $options[] = ['value' => $id, 'label' => $label];
            }
        }

        return $options;
    }

    private function resolveLabel(object $entity): ?string
    {
        if (method_exists($entity, 'getName') && $entity->getName()) {
            return $entity->getName();
        }

        if (method_exists($entity, 'getSize') && $entity->getSize() && method_exists($entity->getSize(), 'getName')) {
            return $entity->getSize()->getName();
        }

        return null;
    }
}
