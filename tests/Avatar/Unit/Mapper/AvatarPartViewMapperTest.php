<?php

namespace App\Tests\Avatar\Unit\Mapper;

use App\Application\Avatar\Dto\AvatarPartViewDto;
use App\Application\Avatar\Mapper\AvatarPartViewMapper;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('avatar')]
#[Group('unit')]
final class AvatarPartViewMapperTest extends TestCase
{
    public function testItMapsAnAvatarPartToATypedDto(): void
    {
        $color = new class {
            public function getId(): int
            {
                return 8;
            }

            public function getName(): string
            {
                return 'Bleu';
            }

            public function getHexa(): string
            {
                return '12abEF';
            }
        };
        $avatarPart = new class ($color) {
            public function __construct(private readonly object $color)
            {
            }

            public function getId(): int
            {
                return 42;
            }

            public function getName(): string
            {
                return 'eyes__bleu__rond.png';
            }

            public function getImage(): string
            {
                return '/images/upload/avatar/eyes/42.png';
            }

            public function getColor(): object
            {
                return $this->color;
            }
        };

        $dto = (new AvatarPartViewMapper())->map($avatarPart);

        self::assertInstanceOf(AvatarPartViewDto::class, $dto);
        self::assertSame(42, $dto->id);
        self::assertSame('eyes__bleu__rond.png', $dto->name);
        self::assertSame('/images/upload/avatar/eyes/42.png', $dto->imageUrl);
        self::assertSame(['/images/upload/avatar/eyes/42.png'], $dto->imageUrls);
        self::assertSame([], $dto->imageSides);
        self::assertSame([
            'Couleur' => ['name' => 'Bleu', 'hexa' => '#12ABEF'],
        ], $dto->attributes);
    }

    public function testItMapsFrontAndBackImages(): void
    {
        $avatarPart = new class {
            public function getId(): int
            {
                return 7;
            }

            public function getName(): string
            {
                return 'hair__black__short';
            }

            public function getImages(): array
            {
                return ['front' => '/front.png', 'back' => '/back.png'];
            }
        };

        $dto = (new AvatarPartViewMapper())->map($avatarPart);

        self::assertSame('/front.png', $dto->imageUrl);
        self::assertSame(['/front.png', '/back.png'], $dto->imageUrls);
        self::assertSame(['front' => '/front.png', 'back' => '/back.png'], $dto->imageSides);
    }
}
