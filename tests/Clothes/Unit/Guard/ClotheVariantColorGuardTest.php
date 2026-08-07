<?php

namespace App\Tests\Clothes\Unit\Guard;

use App\Application\Clothes\Guard\ClotheVariantColorGuard;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\ClothesVariant;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/** Vérifie qu’une couleur ne peut former qu’un seul groupe de variantes par vêtement. */
#[Group('clothes')]
#[Group('unit')]
final class ClotheVariantColorGuardTest extends TestCase
{
    public function testCurrentColorCanBeKept(): void
    {
        $blue = (new Clothescolor())->setName('Bleu');
        $clothe = (new Clothes())->addVariant((new ClothesVariant())->setColor($blue));

        (new ClotheVariantColorGuard())->assertAvailable($clothe, $blue, $blue);

        self::addToAssertionCount(1);
    }

    public function testUnusedColorCanReplaceCurrentColor(): void
    {
        $blue = (new Clothescolor())->setName('Bleu');
        $green = (new Clothescolor())->setName('Vert');
        $clothe = (new Clothes())->addVariant((new ClothesVariant())->setColor($blue));

        (new ClotheVariantColorGuard())->assertAvailable($clothe, $green, $blue);

        self::addToAssertionCount(1);
    }

    public function testColorAlreadyUsedByAnotherGroupIsRejectedRegardlessOfCase(): void
    {
        $blue = (new Clothescolor())->setName('Bleu');
        $red = (new Clothescolor())->setName('Rouge');
        $submittedRed = (new Clothescolor())->setName('rouge');
        $clothe = (new Clothes())
            ->addVariant((new ClothesVariant())->setColor($blue))
            ->addVariant((new ClothesVariant())->setColor($red));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le vêtement possède déjà des variantes de couleur rouge.');

        (new ClotheVariantColorGuard())->assertAvailable($clothe, $submittedRed, $blue);
    }
}
