<?php

namespace App\Tests\Clothes\Unit;

use App\Entity\MeasurementType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/** Vérifie que l’identité technique d’un item de guide est générée sans intervention utilisateur. */
#[Group('clothes')]
#[Group('unit')]
final class MeasurementTypeTest extends TestCase
{
    public function testUuidIsGeneratedAndUniqueAtConstruction(): void
    {
        $first = new MeasurementType();
        $second = new MeasurementType();

        self::assertTrue(
            Uuid::isValid($first->getUuid()->toRfc4122()),
            'Blocage : un item de guide nouvellement créé ne possède pas d’UUID valide.',
        );
        self::assertNotSame(
            $first->getUuid()->toRfc4122(),
            $second->getUuid()->toRfc4122(),
            'Blocage : deux items de guide ont reçu la même identité technique.',
        );
    }
}
