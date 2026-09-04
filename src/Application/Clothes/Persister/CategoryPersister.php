<?php

namespace App\Application\Clothes\Persister;

use App\Entity\Category\Category;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CategoryPersister
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function save(Category $category): void
    {
        $this->entityManager->persist($category);
        $this->entityManager->flush();
    }

    public function delete(Category $category): void
    {
        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }
}
