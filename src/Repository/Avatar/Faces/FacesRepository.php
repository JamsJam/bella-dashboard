<?php

namespace App\Repository\Avatar\Faces;

use App\Entity\Avatar\Faces\Faces;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Faces>
 */
class FacesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Faces::class);
    }

    /**
     * //     * @return Nose[] Returns an array of Body objects
     * //     */
    public function findAllByFilters(
        array $skincolor = [],
        array $shape = [],
    ): array {
        $qb = $this->createQueryBuilder('f');

        if (!empty($skincolor)) {
            $qb->leftJoin('f.skincolor', 'sc');
            $or = $qb->expr()->orX();
            foreach ($skincolor as $i => $id) {
                $param = 'skincolor_'.$i;
                $or->add($qb->expr()->eq('sc.id', ':'.$param));
                $qb->setParameter($param, $id);

                $qb->andWhere($or);
            }
        }

        if (!empty($shape)) {
            $qb->leftJoin('f.shape', 's');
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
    //     * @return Faces[] Returns an array of Faces objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Faces
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
