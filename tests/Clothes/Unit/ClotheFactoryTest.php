<?php

namespace App\Tests\Clothes\Unit;

use App\Application\Clothes\Factory\ClotheFactory;
use App\Entity\Collections\Collections;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('clothes')]
#[Group('unit')]
final class ClotheFactoryTest extends TestCase
{
    public function testItCreatesAClotheEntity(): void
    {
        $collection = new Collections();

        $clothe = (new ClotheFactory())->create(
            name: 'Robe Éclipse',
            price: 5900,
            collection: $collection,
        );

        self::assertSame('Robe Éclipse', $clothe->getName());
        self::assertSame(5900, $clothe->getPrice());
        self::assertSame($collection, $clothe->getCollection());
        self::assertInstanceOf(\DateTimeImmutable::class, $clothe->getCreatedAt());
    }
}
