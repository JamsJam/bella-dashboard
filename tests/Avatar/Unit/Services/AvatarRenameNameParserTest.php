<?php

namespace App\Tests\Avatar\Unit\Services;

use App\Application\Avatar\Services\AvatarRenameNameParser;
use App\Entity\AvatarTemp;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/** Vérifie la conversion d’un nom final en message de renommage. */
#[Group('avatar')]
#[Group('unit')]
final class AvatarRenameNameParserTest extends TestCase
{
    public function testItRebuildsHairPayloadFromFinalName(): void
    {
        $avatar = (new AvatarTemp())->setFinalName('hair__brown__curly__front.png');

        $message = (new AvatarRenameNameParser())->fromAvatarTemp($avatar);

        self::assertSame('hair', $message->category);
        self::assertSame('hair__brown__curly__front.png', $message->newName);
        self::assertSame(['color' => 'brown', 'shape' => 'curly', 'side' => 'front'], $message->filters);
    }

    public function testItMapsVisageToFace(): void
    {
        $avatar = (new AvatarTemp())->setFinalName('visage__clair__ovale__-none-.png');

        $message = (new AvatarRenameNameParser())->fromAvatarTemp($avatar);

        self::assertSame('face', $message->category);
        self::assertSame(['skinColor' => 'clair', 'shape' => 'ovale', 'accessory' => '-none-'], $message->filters);
    }

    public function testItRejectsAnIncompleteName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new AvatarRenameNameParser())->fromAvatarTemp(
            (new AvatarTemp())->setFinalName('eyes__blue.png'),
        );
    }
}
