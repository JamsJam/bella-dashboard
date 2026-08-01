<?php

namespace App\Repository\Reviews;

use App\Entity\Clothes\Clothes;
use App\Entity\Reviews\Review;
use App\Enum\ReviewStatus;
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

    /** @return list<Review> */
    public function findAcceptedByClothes(Clothes $clothes): array
    {
        return $this->createQueryBuilder('review')
            ->join('review.product', 'variant')
            ->andWhere('variant.clothes = :clothes')
            ->andWhere('review.status = :status')
            ->setParameter('clothes', $clothes)
            ->setParameter('status', ReviewStatus::Accepted)
            ->orderBy('review.updatedAt', 'DESC')
            ->addOrderBy('review.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
