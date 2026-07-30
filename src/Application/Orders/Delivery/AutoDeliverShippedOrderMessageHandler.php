<?php

namespace App\Application\Orders\Delivery;

use App\Entity\Orders\Orders;
use App\Enum\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AutoDeliverShippedOrderMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MarkOrderDeliveredService $markOrderDelivered,
    ) {
    }

    public function __invoke(AutoDeliverShippedOrderMessage $message): void
    {
        $this->entityManager->wrapInTransaction(function () use ($message): void {
            $order = $this->entityManager->getRepository(Orders::class)->findForUpdate($message->orderId);
            if (
                !$order instanceof Orders
                || $order->getOrderStatus() !== OrderStatus::Shipped
                || $order->getTrackingNumber() !== $message->trackingNumber
            ) {
                return;
            }

            $this->markOrderDelivered->mark($order);
        });
    }
}
