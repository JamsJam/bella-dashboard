<?php

namespace App\Application\Avatar\Provider;

use Doctrine\ORM\EntityManagerInterface;

final readonly class AvatarColorProvider
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param class-string $entityClass
     *
     * @return list<object>
     */
    public function findAll(string $entityClass): array
    {
        return $this->entityManager->getRepository($entityClass)->findBy([], ['name' => 'ASC']);
    }

    /** @param class-string $entityClass */
    public function find(string $entityClass, int $id): ?object
    {
        return $this->entityManager->find($entityClass, $id);
    }

    /**
     * @param list<string> $associationMethods
     *
     * @return list<object>
     */
    public function associatedElements(object $color, array $associationMethods): array
    {
        $elements = [];

        foreach ($associationMethods as $method) {
            if (!method_exists($color, $method)) {
                continue;
            }

            foreach ($color->{$method}() as $element) {
                if (is_object($element)) {
                    $elements[spl_object_id($element)] = $element;
                }
            }
        }

        return array_values($elements);
    }

    /** @param list<object> $associatedElements */
    public function remove(object $color, array $associatedElements): void
    {
        foreach ($associatedElements as $element) {
            $this->entityManager->remove($element);
        }

        $this->entityManager->remove($color);
        $this->entityManager->flush();
    }
}
