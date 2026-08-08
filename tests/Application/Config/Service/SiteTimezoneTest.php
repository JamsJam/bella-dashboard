<?php

namespace App\Tests\Application\Config\Service;

use App\Application\Config\Service\SiteTimezone;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('integration')]
#[Group('config')]
/** Vérifie que les heures saisies dans le fuseau du site sont réellement stockées en UTC. */
final class SiteTimezoneTest extends KernelTestCase
{
    public function testLocalInputIsConvertedToUtcWithoutChangingTheInstant(): void
    {
        self::bootKernel();
        $siteTimezone = self::getContainer()->get(SiteTimezone::class);
        $input = '2026-08-08T12:30';

        $utc = $siteTimezone->localInputToUtc($input);
        $expected = (new \DateTimeImmutable($input, $siteTimezone->timezone()))
            ->setTimezone(new \DateTimeZone('UTC'));

        self::assertSame(
            'UTC',
            $utc->getTimezone()->getName(),
            'Blocage : la date programmée n’est pas normalisée en UTC avant son enregistrement.',
        );
        self::assertSame(
            $expected->format(\DateTimeInterface::ATOM),
            $utc->format(\DateTimeInterface::ATOM),
            'Blocage : la conversion décale l’instant choisi par l’administrateur.',
        );
    }

    public function testInvalidLocalInputIsRejected(): void
    {
        self::bootKernel();
        $siteTimezone = self::getContainer()->get(SiteTimezone::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Choisissez une date et une heure valides.');

        $siteTimezone->localInputToUtc('2026-02-31T12:30');
    }
}
