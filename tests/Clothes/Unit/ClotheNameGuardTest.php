<?php

namespace App\Tests\Clothes\Unit;

use App\Application\Clothes\Guard\ClotheNameGuard;
use App\Repository\Clothes\ClothesRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/** Vérifie que la garde conserve le nom commercial complet du vêtement. */
#[Group('clothes')]
#[Group('unit')]
final class ClotheNameGuardTest extends TestCase
{
    public function testNormalizationKeepsEveryWordAndCompactsWhitespace(): void
    {
        $guard = new ClotheNameGuard($this->createStub(ClothesRepository::class));

        self::assertSame(
            'Robe longue Été',
            $guard->normalizeName("  Robe   longue\tÉté  "),
            'Blocage : le nom du vêtement est tronqué ou sa casse est perdue pendant la création.',
        );
    }
}
