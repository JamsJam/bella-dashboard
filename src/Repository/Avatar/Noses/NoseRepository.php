<?php

namespace App\Repository\Avatar\Noses;

use App\Entity\Avatar\Noses\Nose;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Nose>
 */
class NoseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Nose::class);
    }

        /**
    //     * @return Nose[] Returns an array of Body objects
    //     */
    public function findAllByFilters(
        array $skincolor,
        array $shape,

    ): array
    {
        $qb = $this->createQueryBuilder('n');

        if ( !empty($skincolor)) {
            $qb->leftJoin('n.skincolor', 'sc');
            $or = $qb->expr()->orX(); 
            foreach ($skincolor as $i => $id) {
                $param = 'skincolor_'.$i;          
                $or->add($qb->expr()->eq('sc.id', ':'.$param));
                $qb->setParameter($param, $id);

                $qb->andWhere($or);
            }
        }

        if ( !empty($shape)) {
            $qb->leftJoin('n.shape', 's');
            $or = $qb->expr()->orX(); 
            foreach ($shape as $i => $id) {
                $param = 'shape_'.$i;  
                $or->add($qb->expr()->eq('s.id', ':'.$param));
                $qb->setParameter($param, $id);

                $qb->andWhere($or);
            }
        }

        return 
        $qb
            ->getQuery()
            // ->getResult()
            ->getArrayResult()
        ;
    }
    //    /**
    //     * @return Nose[] Returns an array of Nose objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('n.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Nose
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
