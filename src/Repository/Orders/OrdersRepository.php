<?php

namespace App\Repository\Orders;

use App\Entity\Orders\Orders;
use App\Entity\Users\Customers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Orders>
 */
class OrdersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Orders::class);
    }

    /** @return array{revenue: int, orders: int, pending: int} */
    public function getDashboardSummary(\DateTimeImmutable $since): array
    {
        $summary = $this->createQueryBuilder('o')
            ->select('COALESCE(SUM(CASE WHEN o.status = :paidStatus THEN o.total ELSE 0 END), 0) AS revenue')
            ->addSelect('COUNT(o.id) AS orders')
            ->addSelect("SUM(CASE WHEN o.status IN ('pending', 'processing') THEN 1 ELSE 0 END) AS pending")
            ->andWhere('o.createdAt >= :since')
            ->setParameter('since', $since)
            ->setParameter('paidStatus', Orders::STATUS_PAID)
            ->getQuery()
            ->getSingleResult();

        return array_map('intval', $summary);
    }

    /** @return list<Orders> */
    public function findLatest(int $limit = 5): array
    {
        return $this->createQueryBuilder('o')
            ->addSelect('customer')
            ->leftJoin('o.customer', 'customer')
            ->orderBy('o.createdAt', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findForUpdate(int $id): ?Orders
    {
        $order = $this->createQueryBuilder('orders')
            ->andWhere('orders.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();

        return $order instanceof Orders ? $order : null;
    }

    /** @return list<Orders> */
    public function findPaidByCustomer(Customers $customer): array
    {
        return $this->createQueryBuilder('orders')
            ->addSelect('cart', 'items')
            ->innerJoin('orders.cart', 'cart')
            ->leftJoin('cart.items', 'items')
            ->andWhere('orders.customer = :customer')
            ->andWhere('orders.status = :status')
            ->setParameter('customer', $customer)
            ->setParameter('status', Orders::STATUS_PAID)
            ->orderBy('orders.createdAt', 'DESC')
            ->addOrderBy('orders.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Orders[] Returns an array of Orders objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Orders
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
