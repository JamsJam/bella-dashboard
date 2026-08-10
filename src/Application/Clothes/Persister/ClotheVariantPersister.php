<?php

namespace App\Application\Clothes\Persister;

use App\Entity\Clothes\ClothesVariant;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ClotheVariantPersister
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function persist(ClothesVariant $variant): void
    {
        $this->entityManager->persist($variant);
    }

    /**
     * @param iterable<ClothesVariant> $variants
     */
    public function saveAll(iterable $variants): void
    {
        foreach ($variants as $variant) {
            $this->persist($variant);
        }

        $this->entityManager->flush();
    }
}
