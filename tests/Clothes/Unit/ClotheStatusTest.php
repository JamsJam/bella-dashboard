<?php

namespace App\Tests\Clothes\Unit;

use App\Enum\ClotheStatus;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/** Vérifie les règles autonomes de progression des statuts, sans démarrer Symfony. */
#[Group('clothes')]
#[Group('unit')]
final class ClotheStatusTest extends TestCase
{
    public function testPublicationStatusesFollowTheExpectedProgressionOrder(): void
    {
        self::assertSame(
            [
                ClotheStatus::Draft,
                ClotheStatus::Publishable,
                ClotheStatus::Scheduled,
                ClotheStatus::Online,
                ClotheStatus::Offline,
                ClotheStatus::Archived,
            ],
            ClotheStatus::cases(),
            'Blocage : les statuts des vêtements ne suivent plus l’ordre métier attendu.',
        );

        self::assertSame(
            [0, 1, 2, 3, 4, 5],
            array_map(
                static fn (ClotheStatus $status): int => $status->progressionRank(),
                ClotheStatus::cases(),
            ),
            'Blocage : le classement utilisé par l’index ne correspond plus à la progression du workflow.',
        );
    }
}
