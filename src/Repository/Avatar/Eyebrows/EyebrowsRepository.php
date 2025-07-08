<?php

namespace App\Repository\Avatar\Eyebrows;

use App\Entity\Avatar\Eyebrows\Eyebrows;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Eyebrows>
 */
class EyebrowsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Eyebrows::class);
    }

    /**
    * @return Eyebrows[] Returns an array of Body objects
    */
    public function findAllByFilters(
        array $color,
        array $shape,
    ): array
    {
        $qb = $this->createQueryBuilder('e');

        if ( !empty($color)) {
            $qb->leftJoin('e.color', 'c');
            $or = $qb->expr()->orX(); 
            foreach ($color as $i => $id) {
                $param = 'color_'.$i;          
                $or->add($qb->expr()->eq('c.id', ':'.$param));
                $qb->setParameter($param, $id);

                $qb->andWhere($or);
            }
        }

        if ( !empty($shape)) {
            $qb->leftJoin('e.shape', 's');
            $or = $qb->expr()->orX(); 
            foreach ($shape as $i => $id) {
                $param = 'shape_'.$i;  
                $or->add($qb->expr()->eq('s.id', ':'.$param));
                $qb->setParameter($param, $id);

                $qb->andWhere($or);
            }
        }

        return $qb->getQuery()->getArrayResult();
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
