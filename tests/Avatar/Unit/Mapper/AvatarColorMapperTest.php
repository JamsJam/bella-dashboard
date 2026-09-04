<?php

namespace App\Tests\Avatar\Unit\Mapper;

use App\Application\Avatar\Dto\AvatarColorDto;
use App\Application\Avatar\Mapper\AvatarColorMapper;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('avatar')]
#[Group('unit')]
final class AvatarColorMapperTest extends TestCase
{
    public function testItMapsAColorToATypedDto(): void
    {
        $color = new class {
            public function getId(): int
            {
                return 5;
            }

            public function getName(): string
            {
                return 'Bleu nuit';
            }

            public function getHexa(): string
            {
                return '001122';
            }
        };

        $dto = (new AvatarColorMapper())->map($color, 'eyes', 3);

        self::assertInstanceOf(AvatarColorDto::class, $dto);
        self::assertSame(5, $dto->id);
        self::assertSame('eyes', $dto->type);
        self::assertSame('Bleu nuit', $dto->name);
        self::assertSame('001122', $dto->hexa);
        self::assertSame(3, $dto->associatedCount);
    }

    public function testItRejectsAnUnpersistedColor(): void
    {
        $color = new class {
            public function getId(): ?int
            {
                return null;
            }

            public function getName(): string
            {
                return 'Bleu';
            }

            public function getHexa(): string
            {
                return '0000FF';
            }
        };

        $this->expectException(\LogicException::class);
        (new AvatarColorMapper())->map($color, 'eyes', 0);
    }
}
