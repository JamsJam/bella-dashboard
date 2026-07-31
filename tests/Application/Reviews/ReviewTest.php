<?php

namespace App\Tests\Application\Reviews;

use App\Entity\Clothes\ClothesVariant;
use App\Entity\Orders\Orders;
use App\Entity\Reviews\Review;
use App\Entity\Users\Customers;
use App\Enum\ReviewStatus;
use PHPUnit\Framework\TestCase;

final class ReviewTest extends TestCase
{
    public function testWorkflowFromRequestToAcceptance(): void
    {
        $requestedAt = new \DateTimeImmutable('2026-07-31 12:00:00');
        $review = new Review(new ClothesVariant(), new Orders(), new Customers(), $requestedAt);

        self::assertSame(ReviewStatus::Requested, $review->getStatus());
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $review->getReviewUuid());
        self::assertNull($review->getRating());

        $review->submit(5, '  Très beau produit.  ', $requestedAt->modify('+1 day'));
        self::assertSame(ReviewStatus::Pending, $review->getStatus());
        self::assertSame('Très beau produit.', $review->getComment());

        $review->accept('Merci pour votre retour.', $requestedAt->modify('+2 days'));
        self::assertSame(ReviewStatus::Accepted, $review->getStatus());
        self::assertSame('Merci pour votre retour.', $review->getReply());
        self::assertNotNull($review->getReplyAt());
    }

    public function testRatingMustBeBetweenOneAndFive(): void
    {
        $review = new Review(new ClothesVariant(), new Orders(), new Customers());
        $this->expectException(\InvalidArgumentException::class);
        $review->submit(6, 'Commentaire');
    }

    public function testSubmittedReviewCannotBeSubmittedTwice(): void
    {
        $review = new Review(new ClothesVariant(), new Orders(), new Customers());
        $review->submit(4, 'Commentaire');
        $this->expectException(\DomainException::class);
        $review->submit(3, 'Autre commentaire');
    }
}
