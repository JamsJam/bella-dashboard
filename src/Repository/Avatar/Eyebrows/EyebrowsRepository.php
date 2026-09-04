<?php

namespace App\Repository\Avatar\Eyebrows;

use App\Application\Avatar\Interface\AvatarPartModelInterface;
use App\Entity\Avatar\Eyebrows\Eyebrows;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Eyebrows>
 */
class EyebrowsRepository extends ServiceEntityRepository implements AvatarPartModelInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Eyebrows::class);
    }

    /**
     * @return Eyebrows[] Returns an array of Eyebrows objects
     */
    public function findAllByFilters(
        ?int $color = null,
        ?int $shape = null,
    ): array {
        $qb = $this->createQueryBuilder('e');

        if (0 !== $color && null !== $color) {
            $qb->leftJoin('e.color', 'c')
                ->andWhere('c.id = :color')
                ->setParameter('color', $color);
        }

        if (0 !== $shape && null !== $shape) {
            $qb->leftJoin('e.shape', 's')
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

    //    /**
    //     * @return eyebrows[] Returns an array of eyebrows objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?eyebrows
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
