<?php

namespace App\Tests\Clothes\Unit;

use App\Application\Clothes\Services\Clothe\ClotheCompletenessChecker;
use App\Entity\Category\Category;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Collections\Collections;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('clothes')]
#[Group('unit')]
/** Vérifie les informations obligatoires avant publication d’une variante. */
final class ClotheCompletenessCheckerTest extends TestCase
{
    public function testDescriptionAndStockAreNotPublicationRequirements(): void
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
                ->setMetadescription('Découvrez notre robe rouge en taille M.')
                ->setImages(['/image.jpg'])
                ->setStock(0),
        );

        self::assertTrue(
            (new ClotheCompletenessChecker())->check($clothe)->isComplete(),
            'Blocage : le stock ou la description empêchent à tort la publication.',
        );
    }

    public function testSeoDescriptionIsRequiredForPublication(): void
    {
        $clothe = (new Clothes())
            ->setName('Robe')
            ->setPrice(12000)
            ->setCollection(new Collections());
        $clothe->addVariant(
            (new ClothesVariant())
                ->setName('Robe Rouge M')
                ->setSlug('robe-rouge')
                ->setSku('ROBE-ROUGE-M')
                ->setColor((new Clothescolor())->setName('Rouge'))
                ->setSize((new Clothessize())->setName('M'))
                ->setImages(['/image.jpg']),
        );

        $result = (new ClotheCompletenessChecker())->check($clothe);

        self::assertFalse(
            $result->isComplete(),
            'Blocage : une variante sans métadescription SEO est considérée comme complète.',
        );
        self::assertStringContainsString(
            'meta description SEO',
            implode(' ', $result->errors()),
            'Blocage : l’erreur de complétude n’explique pas que la métadescription SEO est obligatoire.',
        );
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
        self::assertFalse($result->isComplete(), 'Blocage : une variante sans image est considérée comme complète.');
        self::assertStringContainsString(
            'image',
            implode(' ', $result->errors()),
            'Blocage : l’erreur de complétude n’explique pas que l’image est obligatoire.',
        );
    }
}
