<?php

namespace App\ApiResource\Variant;

final readonly class VariantReviewDTO
{
    public function __construct(
        public int $rating,
        public string $comment,
        public string $createdAt,
        public ?string $reply = null,
        public ?string $repliedAt = null,
    ) {
    }
}
