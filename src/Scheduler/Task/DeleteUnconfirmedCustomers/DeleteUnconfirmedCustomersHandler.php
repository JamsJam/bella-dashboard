<?php

namespace App\Scheduler\Task\DeleteUnconfirmedCustomers;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DeleteUnconfirmedCustomersHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(DeleteUnconfirmedCustomersMessage $message): void
    {
        $customers = $message->getCustomers();
        foreach ($customers as $customer) {
            $this->entityManager->remove($customer);
        }
        $this->entityManager->flush();
    }
}
