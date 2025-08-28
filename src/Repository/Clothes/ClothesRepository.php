<?php

namespace App\Repository\Clothes;

use App\Entity\Clothes\Clothes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Clothes>
 */
class ClothesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Clothes::class);
    }

    /**
    * @return Clothes[] Returns an array of Clothes objects
    */
    public function findClothesInCollection($collection): array
    {
        $qb= $this->createQueryBuilder('c');
        $qb->select('DISTINCT c.name, c.images, c.isOnline, co.name As collectionName, cc.name AS colorName, c.createdAt')
            ->leftJoin('c.collection','co')
            ->leftJoin('c.size','cs')
            ->leftJoin('c.color','cc')
            ->andWhere($qb->expr()->eq('co.id', ':collection'))
            ->setParameter(':collection', $collection->getId())

            ->orderBy('c.createdAt', 'ASC')
            
            ;
            dd($qb->getQuery()->getResult());
        return $qb->getQuery()->getResult();
    }

    //    public function findOneBySomeField($value): ?Clothes
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
