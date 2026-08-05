<?php

namespace App\Tests\Application\Clothes;

use App\Application\Clothes\Services\ClotheCompletenessChecker;
use App\Entity\Category\Category;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use App\Entity\Collections\Collections;
use PHPUnit\Framework\TestCase;

final class ClotheCompletenessCheckerTest extends TestCase
{
    public function testDescriptionMetaAndStockAreNotPublicationRequirements(): void
    {
        $collection = (new Collections())->setName('Été')->setCategory(new Category());
        $color = (new Clothescolor())->setName('Rouge');
        $size = (new Clothessize())->setName('M');
        $clothe = (new Clothes())->setName('Robe')->setPrice(12000)->setCollection($collection);
        $clothe->addVariant(
            (new ClothesVariant())
                ->setName('Robe Rouge M')
                ->setSlug('robe-rouge')
                ->setSku('ROBE-ROUGE-M')
                ->setColor($color)
                ->setSize($size)
                ->setImages(['/image.jpg'])
                ->setStock(0),
        );

        self::assertTrue((new ClotheCompletenessChecker())->check($clothe)->isComplete());
    }

    public function testAnImageIsRequiredForEveryColorGroup(): void
    {
        $clothe = (new Clothes())->setName('Robe')->setPrice(12000)->setCollection(new Collections());
        $clothe->addVariant(
            (new ClothesVariant())
                ->setName('Robe Rouge M')
                ->setSlug('robe-rouge')
                ->setSku('ROBE-ROUGE-M')
                ->setColor((new Clothescolor())->setName('Rouge'))
                ->setSize((new Clothessize())->setName('M')),
        );

        $result = (new ClotheCompletenessChecker())->check($clothe);
        self::assertFalse($result->isComplete());
        self::assertStringContainsString('image', implode(' ', $result->errors()));
    }
}
