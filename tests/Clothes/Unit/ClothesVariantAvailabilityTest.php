<?php

namespace App\Tests\Clothes\Unit;

use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Enum\ClotheStatus;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('clothes')]
#[Group('unit')]
/** Vérifie la disponibilité commerciale calculée directement par la variante. */
final class ClothesVariantAvailabilityTest extends TestCase
{
    public function testAnOnlineVariantWithStockIsAvailableRegardlessOfTheLegacyParentStatus(): void
    {
        $clothe = new Clothes();
        $variant = (new ClothesVariant())
            ->setClothes($clothe)
            ->setPublicationStatus(ClotheStatus::Online)
            ->setStock(1);

        self::assertTrue($variant->isAvailable(), 'Blocage : une variante en ligne avec du stock est indisponible.');
    }

    public function testAnOfflineOrOutOfStockVariantIsUnavailable(): void
    {
        $variant = (new ClothesVariant())->setPublicationStatus(ClotheStatus::Offline)->setStock(1);
        self::assertFalse($variant->isAvailable(), 'Blocage : une variante hors ligne est affichée comme disponible.');

        $variant->setPublicationStatus(ClotheStatus::Online)->setStock(0);
        self::assertFalse($variant->isAvailable(), 'Blocage : une variante sans stock est affichée comme disponible.');
    }
}
