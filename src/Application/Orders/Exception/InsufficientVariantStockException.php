<?php

namespace App\Application\Orders\Exception;

final class InsufficientVariantStockException extends \DomainException
{
    public function __construct(
        public readonly int $variantId,
        public readonly int $requestedQuantity,
        public readonly int $availableQuantity,
    ) {
        parent::__construct(sprintf(
            'Insufficient stock for variant %d: %d requested, %d available.',
            $variantId,
            $requestedQuantity,
            $availableQuantity,
        ));
    }
}
