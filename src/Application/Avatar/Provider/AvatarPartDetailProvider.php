<?php

namespace App\Application\Avatar\Provider;

use App\Entity\Avatar\Faces\Faces;
use App\Repository\Avatar\Faces\FacesRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AvatarPartDetailProvider
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /** @param class-string $entityClass */
    public function find(string $entityClass, int $id): ?object
    {
        return $this->entityManager->find($entityClass, $id);
    }

    /**
     * @param class-string $entityClass
     *
     * @return list<object>
     */
    public function findSimilar(string $entityClass, object $avatarPart, int $limit = 12): array
    {
        $allParts = $this->entityManager->getRepository($entityClass)->findAll();

        return array_slice(array_values(array_filter(
            $allParts,
            fn (object $candidate): bool => $this->isSimilar($avatarPart, $candidate),
        )), 0, $limit);
    }

    /** @return list<Faces> */
    public function findAccessoryFaces(object $avatarPart): array
    {
        if (!$avatarPart instanceof Faces || null !== $avatarPart->getAccessory()) {
            return [];
        }

        $repository = $this->entityManager->getRepository(Faces::class);

        return $repository instanceof FacesRepository ? $repository->findAccessorizedFor($avatarPart) : [];
    }

    private function isSimilar(object $reference, object $candidate): bool
    {
        if ($this->resolveId($reference) === $this->resolveId($candidate)) {
            return false;
        }

        $hasComparison = false;

        foreach (['getShape', 'getColor', 'getSkincolor', 'getMorphotype', 'getAccessory'] as $getter) {
            if (!method_exists($reference, $getter) || !method_exists($candidate, $getter)) {
                continue;
            }

            $hasComparison = true;

            if ($this->resolveId($reference->{$getter}()) !== $this->resolveId($candidate->{$getter}())) {
                return false;
            }
        }

        return $hasComparison;
    }

    private function resolveId(?object $entity): ?int
    {
        return null !== $entity && method_exists($entity, 'getId') ? $entity->getId() : null;
    }
}
