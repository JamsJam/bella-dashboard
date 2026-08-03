<?php

namespace App\Tests\Application\Clothes;

use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use PHPUnit\Framework\TestCase;

final class ClothesVariantAvailabilityTest extends TestCase
{
    public function testAnOnlineVariantWithStockIsAvailableRegardlessOfTheLegacyParentStatus(): void
    {
        $clothe = (new Clothes())->setIsOnline(false);
        $variant = (new ClothesVariant())
            ->setClothes($clothe)
            ->setIsOnline(true)
            ->setStock(1);

        self::assertTrue($variant->isAvailable());
    }

    public function testAnOfflineOrOutOfStockVariantIsUnavailable(): void
    {
        $variant = (new ClothesVariant())->setIsOnline(false)->setStock(1);
        self::assertFalse($variant->isAvailable());

        $variant->setIsOnline(true)->setStock(0);
        self::assertFalse($variant->isAvailable());
    }
}
