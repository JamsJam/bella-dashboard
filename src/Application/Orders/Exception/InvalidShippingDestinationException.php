<?php

namespace App\Application\Orders\Exception;

final class InvalidShippingDestinationException extends \DomainException
{
    public function __construct(string $destination)
    {
        parent::__construct(sprintf('No shipping fee is configured for destination "%s".', $destination));
    }
}
