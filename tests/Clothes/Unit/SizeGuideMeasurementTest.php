<?php

namespace App\Tests\Clothes\Unit;

use App\Entity\SizeGuideMeasurement;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('clothes')]
#[Group('unit')]
final class SizeGuideMeasurementTest extends TestCase
{
    public function testItAcceptsAFreeTextMinimumAndMaximum(): void
    {
        $measurement = (new SizeGuideMeasurement())->setValue('80 à 90');

        self::assertSame('80 à 90', $measurement->getValue());
    }
}
