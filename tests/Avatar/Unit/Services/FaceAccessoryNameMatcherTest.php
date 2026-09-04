<?php

namespace App\Tests\Avatar\Unit\Services;

use App\Application\Avatar\Services\FaceAccessoryNameMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/** Vérifie la détection des accessoires encodés dans le nom d’un visage. */
#[Group('avatar')]
#[Group('unit')]
final class FaceAccessoryNameMatcherTest extends TestCase
{
    #[DataProvider('names')]
    public function testItIdentifiesAccessorizedFaces(string $name, bool $expected): void
    {
        self::assertSame($expected, (new FaceAccessoryNameMatcher())->matches($name));
    }

    public static function names(): iterable
    {
        yield 'accessory' => ['visage__clair__ovale__lunettes', true];
        yield 'without accessory' => ['visage__clair__ovale__-none-', false];
    }

    #[DataProvider('namesWithoutAccessory')]
    public function testItIdentifiesFacesWithoutAccessory(string $name, bool $expected): void
    {
        self::assertSame($expected, (new FaceAccessoryNameMatcher())->matchesWithoutAccessory($name));
    }

    public static function namesWithoutAccessory(): iterable
    {
        yield 'without accessory' => ['visage__clair__ovale__-none-', true];
        yield 'with accessory' => ['visage__clair__ovale__lunettes', false];
        yield 'none is not the suffix' => ['visage__clair__ovale__-none-__extra', false];
    }

    #[DataProvider('invalidNames')]
    public function testItRejectsAnInvalidFaceName(string $name): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new FaceAccessoryNameMatcher())->matches($name);
    }

    public static function invalidNames(): iterable
    {
        yield 'missing last segment' => ['visage__clair__ovale'];
        yield 'empty last segment' => ['visage__clair__ovale__'];
    }
}
