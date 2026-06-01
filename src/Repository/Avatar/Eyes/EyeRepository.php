<?php

namespace App\Repository\Avatar\Eyes;

use App\Application\Avatar\Interface\AvatarPartModelInterface;
use App\Entity\Avatar\Eyes\Eye;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Eye>
 */
class EyeRepository extends ServiceEntityRepository implements AvatarPartModelInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Eye::class);
    }

    /**
     * @return Eye[] Returns an array of Eye objects
     */
    public function findAllByFilters(
        array $filter = []
    ): array {
        $color = $filter['color'] ?? null;
        $shape = $filter['shape'] ?? null;
        
        $qb = $this->createQueryBuilder('e');

        if ($color !== 0 && $color !== null) {
            $qb->leftJoin('e.color', 'c')
                ->andWhere('c.id = :color')
                ->setParameter('color', $color);
        }

        if ($shape !== 0 && $shape !== null) {
            $qb->leftJoin('e.shape', 's')
                ->andWhere('s.id = :shape')
                ->setParameter('shape', $shape);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findPartByFilters(array $filters = []): array
    {
        return $this->findAllByFilters($filters);
    }

    /**
     * {@inheritdoc}
     */
    public function findAllPart(): array
    {
        return $this->findAll();
    }

    //    /**
    //     * @return Eye[] Returns an array of Eye objects
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

    //    public function findOneBySomeField($value): ?Eye
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
