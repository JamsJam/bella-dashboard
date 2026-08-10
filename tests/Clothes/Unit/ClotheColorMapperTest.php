<?php

namespace App\Tests\Clothes\Unit;

use App\Application\Clothes\DTO\ClotheColorDto;
use App\Application\Clothes\Mapper\ClotheColorMapper;
use App\Entity\Clothes\Clothescolor;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('clothes')]
#[Group('unit')]
final class ClotheColorMapperTest extends TestCase
{
    public function testItMapsAPersistedColorToATypedDto(): void
    {
        $color = (new Clothescolor())->setName('Bleu nuit')->setHexa('001122');
        (new \ReflectionProperty($color, 'id'))->setValue($color, 17);

        $dto = (new ClotheColorMapper())->map($color);

        self::assertInstanceOf(ClotheColorDto::class, $dto);
        self::assertSame(17, $dto->id);
        self::assertSame('Bleu nuit', $dto->name);
        self::assertSame('001122', $dto->hexa);
        self::assertSame(0, $dto->clothesCount);
        self::assertSame(0, $dto->variantsCount);
    }

    public function testItRejectsAnUnpersistedColor(): void
    {
        $this->expectException(\LogicException::class);
        (new ClotheColorMapper())->map(new Clothescolor());
    }
}
