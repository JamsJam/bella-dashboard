<?php

namespace App\Tests\Application\Clothes;

use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Enum\ClotheStatus;
use PHPUnit\Framework\TestCase;

final class ClothesVariantAvailabilityTest extends TestCase
{
    public function testAnOnlineVariantWithStockIsAvailableRegardlessOfTheLegacyParentStatus(): void
    {
        $clothe = new Clothes();
        $variant = (new ClothesVariant())
            ->setClothes($clothe)
            ->setPublicationStatus(ClotheStatus::Online)
            ->setStock(1);

        self::assertTrue($variant->isAvailable());
    }

    public function testAnOfflineOrOutOfStockVariantIsUnavailable(): void
    {
        $variant = (new ClothesVariant())->setPublicationStatus(ClotheStatus::Offline)->setStock(1);
        self::assertFalse($variant->isAvailable());

        $variant->setPublicationStatus(ClotheStatus::Online)->setStock(0);
        self::assertFalse($variant->isAvailable());
    }
}
