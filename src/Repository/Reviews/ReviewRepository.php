<?php

namespace App\Repository\Reviews;

use App\Entity\Reviews\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Review> */
final class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function findOneByUuid(string $uuid): ?Review
    {
        $review = $this->findOneBy(['reviewUuid' => strtolower(trim($uuid))]);
        return $review instanceof Review ? $review : null;
    }
}
