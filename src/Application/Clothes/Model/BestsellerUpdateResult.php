<?php

namespace App\Application\Clothes\Model;

use App\Entity\Clothes\Clothes;

final readonly class BestsellerUpdateResult
{
    /**
     * @param list<Clothes> $bestsellers
     * @param list<Clothes> $overflow
     */
    public function __construct(
        public bool $success,
        public bool $requiresPruning,
        public array $bestsellers,
        public array $overflow,
        public int $maxItems,
        public string $message,
    ) {
    }
}
