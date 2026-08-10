<?php

namespace App\Tests\Avatar\Unit\Resolver;

use App\Application\Avatar\Resolver\AvatarColorTypeResolver;
use App\Entity\Avatar\Skincolor;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('avatar')]
#[Group('unit')]
final class AvatarColorTypeResolverTest extends TestCase
{
    public function testItResolvesAColorTypeDefinition(): void
    {
        $definition = (new AvatarColorTypeResolver())->resolve('skin');

        self::assertSame('skin', $definition->type);
        self::assertSame('Peau', $definition->label);
        self::assertSame(Skincolor::class, $definition->entityClass);
        self::assertSame(['getNoses', 'getBodies', 'getFaces'], $definition->associationMethods);
    }

    public function testItExposesEverySupportedType(): void
    {
        self::assertSame(
            ['skin', 'hair', 'eyes', 'eyebrows', 'mouth'],
            array_map(static fn ($definition): string => $definition->type, (new AvatarColorTypeResolver())->all()),
        );
    }

    public function testItRejectsAnUnknownType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new AvatarColorTypeResolver())->resolve('unknown');
    }
}
