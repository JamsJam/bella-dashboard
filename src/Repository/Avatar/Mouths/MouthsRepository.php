<?php

namespace App\Repository\Avatar\Mouths;

use App\Application\Avatar\Interface\AvatarPartModelInterface;
use App\Entity\Avatar\Mouths\Mouths;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mouths>
 */
class MouthsRepository extends ServiceEntityRepository implements AvatarPartModelInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mouths::class);
    }

<<<<<<< HEAD
    /**
     * @return Mouths[] Returns an array of Mouths objects
     */
    public function findAllByFilters(
        ?int $color = null,
        ?int $shape = null,
    ): array {
        $qb = $this->createQueryBuilder('b');

        if (0 !== $color && null !== $color) {
            $qb->leftJoin('b.color', 'c')
                ->andWhere('c.id = :color')
                ->setParameter('color', $color);
        }

        if (0 !== $shape && null !== $shape) {
            $qb->leftJoin('b.shape', 's')
                ->andWhere('s.id = :shape')
                ->setParameter('shape', $shape);
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function findPartByFilters(array $filters = []): array
    {
        return $this->findAllByFilters(
            $filters['color'] ?? null,
            $filters['shape'] ?? null
        );
    }

    public function findAllPart(): array
    {
        return $this->findAll();
    }

=======
>>>>>>> main
    //    /**
    //     * @return Mouths[] Returns an array of Mouths objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Mouths
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
