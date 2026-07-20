<?php

namespace App\Scheduler\Task\DeleteUnconfirmedCustomers;


final readonly class DeleteUnconfirmedCustomersMessage
{
    public function __construct(
        public array $customers
    ) {}

    public function getCustomers(): array
    {
        return $this->customers;
    }
}
