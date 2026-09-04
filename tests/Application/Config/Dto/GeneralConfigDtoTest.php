<?php

namespace App\Tests\Application\Config\Dto;

use App\Application\Config\Dto\GeneralConfigDto;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('config')]
/** Vérifie que le fuseau du site reste disponible avec les anciennes configurations. */
final class GeneralConfigDtoTest extends TestCase
{
    public function testLegacyConfigurationUsesParisTimezone(): void
    {
        $config = GeneralConfigDto::fromArray(['site_title' => 'Bella']);

        self::assertSame(
            'Europe/Paris',
            $config->timezone,
            'Compatibilité : une configuration existante sans fuseau horaire ne reçoit pas la valeur Europe/Paris.',
        );
    }

    public function testTimezoneIsPersistedInConfigurationPayload(): void
    {
        $config = new GeneralConfigDto(timezone: 'America/Guadeloupe');

        self::assertSame(
            'America/Guadeloupe',
            $config->toArray()['timezone'],
            'Blocage : le fuseau horaire sélectionné disparaît lors de la sauvegarde de la configuration.',
        );
    }
}
