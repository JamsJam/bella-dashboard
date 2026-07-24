<?php

namespace App\Tests\Application\Avatar\Services;

use App\Application\Avatar\Services\FaceAccessoryNameMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

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
        yield 'missing fourth part' => ['visage__clair__ovale', false];
        yield 'empty fourth part' => ['visage__clair__ovale__', false];
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
        yield 'missing fourth part' => ['visage__clair__ovale', false];
    }
}
