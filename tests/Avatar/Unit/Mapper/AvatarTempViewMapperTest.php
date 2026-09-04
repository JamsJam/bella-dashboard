<?php

namespace App\Tests\Avatar\Unit\Mapper;

use App\Application\Avatar\Dto\AvatarTempViewDto;
use App\Application\Avatar\Mapper\AvatarTempViewMapper;
use App\Entity\AvatarTemp;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('avatar')]
#[Group('unit')]
final class AvatarTempViewMapperTest extends TestCase
{
    public function testItMapsAPersistedTemporaryAvatar(): void
    {
        $avatarTemp = (new AvatarTemp())
            ->setOriginalName('original.png')
            ->setStoredName('stored.png')
            ->setStatus('validated')
            ->setFinalName('nose__clair__fin.png');
        (new \ReflectionProperty($avatarTemp, 'id'))->setValue($avatarTemp, 18);

        $dto = (new AvatarTempViewMapper())->map($avatarTemp);

        self::assertInstanceOf(AvatarTempViewDto::class, $dto);
        self::assertSame(18, $dto->id);
        self::assertSame('original.png', $dto->originalName);
        self::assertSame('stored.png', $dto->storedName);
        self::assertSame('validated', $dto->status);
        self::assertSame('nose__clair__fin.png', $dto->finalName);
    }

    public function testItRejectsAnUnpersistedTemporaryAvatar(): void
    {
        $this->expectException(\LogicException::class);
        (new AvatarTempViewMapper())->map(new AvatarTemp());
    }
}
