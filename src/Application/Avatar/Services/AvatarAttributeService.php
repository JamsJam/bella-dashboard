<?php

namespace App\Application\Avatar\Services;

use App\Entity\Avatar\Body\Bodysize;
use App\Entity\Avatar\Body\Morphologie;
use App\Entity\Avatar\Eyebrows\Eyebrowshape;
use App\Entity\Avatar\Eyes\Eyeshape;
use App\Entity\Avatar\Faces\Faceshape;
use App\Entity\Avatar\Hairs\Hairshape;
use App\Entity\Avatar\Mouths\Mouthshape;
use App\Entity\Avatar\Noses\Noseshape;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AvatarAttributeService
{
    private const GROUPS = [
        'shapes' => [
            'face' => ['Visages', Faceshape::class, 'getFaces'],
            'nose' => ['Nez', Noseshape::class, 'getNoses'],
            'mouth' => ['Bouches', Mouthshape::class, 'getMouths'],
            'eyes' => ['Yeux', Eyeshape::class, 'getEyes'],
            'eyebrows' => ['Sourcils', Eyebrowshape::class, 'getEyebrows'],
            'hair' => ['Cheveux', Hairshape::class, 'getHairs'],
            'body' => ['Morphologies', Morphologie::class, 'getMorphotypes'],
        ],
        'sizes' => [
            'body' => ['Corps', Bodysize::class, 'getMorphotypes'],
        ],
    ];

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function modal(string $group, string $type): array
    {
        $definition = $this->definition($group, $type);
        [$label, $entityClass, $associationMethod] = $definition;
        $items = [];

        foreach ($this->entityManager->getRepository($entityClass)->findBy([], ['name' => 'ASC']) as $entity) {
            $items[] = [
                'id' => $entity->getId(),
                'name' => $entity->getName(),
                'associatedCount' => count($entity->{$associationMethod}()),
            ];
        }

        return ['group' => $group, 'type' => $type, 'label' => $label, 'items' => $items, 'tabs' => $this->tabs($group)];
    }

    public function delete(string $group, string $type, int $id): void
    {
        [, $entityClass, $associationMethod] = $this->definition($group, $type);
        $entity = $this->entityManager->find($entityClass, $id);
        if (!is_object($entity)) {
            throw new \InvalidArgumentException('Avatar attribute not found.');
        }

        foreach ($entity->{$associationMethod}() as $associated) {
            if (method_exists($associated, 'getBodies')) {
                foreach ($associated->getBodies() as $body) {
                    $this->entityManager->remove($body);
                }
            }
            $this->entityManager->remove($associated);
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }

    private function definition(string $group, string $type): array
    {
        if (!isset(self::GROUPS[$group][$type])) {
            throw new \InvalidArgumentException('Unknown avatar attribute type.');
        }

        return self::GROUPS[$group][$type];
    }

    private function tabs(string $group): array
    {
        $tabs = [];
        foreach (self::GROUPS[$group] ?? [] as $type => [$label]) {
            $tabs[] = ['type' => $type, 'label' => $label];
        }

        return $tabs;
    }
}
