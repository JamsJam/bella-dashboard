<?php

namespace App\Application\Auth;

use App\Entity\Users\Customers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

final readonly class ExpiredUnconfirmedCustomersPurger
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[AsPeriodicTask('5 minutes')]
    public function purge(): int
    {
        return (int) $this->entityManager
            ->createQueryBuilder()
            ->delete(Customers::class, 'customer')
            ->andWhere('customer.isSignupConfirmed = false')
            ->andWhere('customer.signupVerificationExpiresAt IS NOT NULL')
            ->andWhere('customer.signupVerificationExpiresAt <= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
