<?php

namespace App\Tests\ApiResource\Variant;

use App\ApiResource\Variant\ClothesVariantsDTO;
use App\ApiResource\Variant\VariantCategoryDTO;
use App\ApiResource\Variant\VariantDetailDTO;
use App\ApiResource\Variant\VariantReviewDTO;
use PHPUnit\Framework\TestCase;

final class VariantDetailDTOTest extends TestCase
{
    public function testItExposesPublicReviewsWithoutCustomerOrRequestIdentifiers(): void
    {
        $review = new VariantReviewDTO(
            rating: 5,
            comment: 'Très beau produit.',
            createdAt: '2026-08-01T10:00:00+02:00',
            reply: 'Merci pour votre retour.',
            repliedAt: '2026-08-01T12:00:00+02:00',
        );
        $detail = new VariantDetailDTO(
            name: 'Produit',
            slug: 'produit-noir',
            price: 5900,
            category: new VariantCategoryDTO('Robes', 'robes'),
            clothesVariant: new ClothesVariantsDTO('Produit', []),
            reviews: [$review],
        );

        self::assertSame([$review], $detail->reviews);
        self::assertSame(5, $detail->reviews[0]->rating);
        self::assertObjectNotHasProperty('customerEmail', $detail->reviews[0]);
        self::assertObjectNotHasProperty('reviewUuid', $detail->reviews[0]);
    }
}
