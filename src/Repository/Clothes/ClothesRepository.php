<?php

namespace App\Repository\Clothes;

use App\Entity\Clothes\Clothes;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

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
    /**
    * @return Clothes[] Returns an array of Clothes objects
    */
    public function findDistinctBySlug(?string $orderBy, ?string $direction,?string $query=null, ?int $limit, ?int $offset ): array
    {

        $limit = $limit ?? 10;
        $offset = $offset ?? 0;

        $qb = $this->createQueryBuilder('c');

        $qb ->select('c.slug, c.name, col.name AS collection , cat.name AS category, c.isOnline')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->groupBy('c.slug, c.name, col.name, cat.name, c.isOnline')
            ->orderBy($orderBy,$direction)
            ->setMaxResults( $limit )
        ;

        if (isset($query) && strlen($query) > 0){
            $qb->where(
                $qb->expr()->orX(
                    $qb->expr()->like('c.name', ':query'),
                    $qb->expr()->like('col.name', ':query'),
                    $qb->expr()->like('cat.name', ':query'),
                    $qb->expr()->like('cat.isOnline', ':query')
                )
            )
            ->setParameter('query','%' . $query . '%')
            ;
        }

        return $qb->getQuery()->getResult();
    }
    /**
    * @return Clothes[] Returns an array of Clothes objects
    */
    public function findBestSellersDistinctBySlug(?int $limit ): array
    {

        $qb = $this->createQueryBuilder('c');

        $qb ->select('c.slug, c.name, col.name AS collection , cat.name AS category')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->groupBy('c.slug, c.name, col.name, cat.name')
            ->orderBy('c.name',"asc")
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq(
                    'c.isBestseller', true),
            ))
            ->setMaxResults( $limit )
        ;


        return $qb->getQuery()->getResult();
    }


    /**
    * @return Clothes[] Returns an array of Clothes objects
    */
    public function findInCarouselleDistinctBySlug(?int $limit): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb ->select('c.slug, c.name, col.name AS collection , cat.name AS category')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->groupBy('c.slug, c.name, col.name, cat.name')
            ->orderBy('c.name',"asc")
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq('inCarrousel', true)
            ))
            ->setMaxResults($limit)
        ;


        return $qb->getQuery()->getResult();
    }


}
