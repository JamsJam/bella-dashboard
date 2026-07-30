<?php

namespace App\Tests\Application\Orders\Services;

use App\Application\Orders\Services\OrderReferenceGenerator;
use App\Entity\Orders\Orders;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

final class OrderReferenceGeneratorTest extends TestCase
{
    public function testGeneratesUniqueReferenceWithExpectedFormat(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(self::callback(
                static fn (array $criteria): bool => preg_match(
                    '/^ORDER-'.(new \DateTimeImmutable())->format('Ymd').'-[A-Z0-9]{8}$/',
                    (string) ($criteria['orderReference'] ?? ''),
                ) === 1,
            ))
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(Orders::class)
            ->willReturn($repository);

        $reference = (new OrderReferenceGenerator($entityManager))->generate();

        self::assertMatchesRegularExpression(
            '/^ORDER-'.(new \DateTimeImmutable())->format('Ymd').'-[A-Z0-9]{8}$/',
            $reference,
        );
    }

    public function testRetriesWhenGeneratedReferenceAlreadyExists(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(self::exactly(2))
            ->method('findOneBy')
            ->willReturnOnConsecutiveCalls(new Orders(), null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::exactly(2))
            ->method('getRepository')
            ->with(Orders::class)
            ->willReturn($repository);

        $reference = (new OrderReferenceGenerator($entityManager))->generate();

        self::assertMatchesRegularExpression('/^ORDER-\d{8}-[A-Z0-9]{8}$/', $reference);
    }
}
