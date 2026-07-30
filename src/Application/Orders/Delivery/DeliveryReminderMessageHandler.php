<?php

namespace App\Application\Orders\Delivery;

use App\Entity\Orders\Orders;
use App\Enum\OrderStatus;
use App\Notifier\Services\EmailNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DeliveryReminderMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailNotificationService $emailNotificationService,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(DeliveryReminderMessage $message): void
    {
        $this->entityManager->wrapInTransaction(function () use ($message): void {
            $order = $this->entityManager->getRepository(Orders::class)->findForUpdate($message->orderId);
            if (
                !$order instanceof Orders
                || $order->getOrderStatus() !== OrderStatus::AwaitingDelivery
                || $order->getDeliveryDate()?->format('Y-m-d') !== $message->deliveryDate
                || $order->getDeliveryReminderSentAt() !== null
            ) {
                return;
            }

            $this->emailNotificationService->sendTemplatedAdminEmail(
                subject: sprintf('Livraison imminente pour la commande %s', (string) $order->getOrderReference()),
                template: 'email/delivery_reminder_owner.html.twig',
                context: [
                    'order' => $order,
                    'deliveryDate' => $order->getDeliveryDate(),
                ],
            );

            $order->setDeliveryReminderSentAt($this->clock->now());
            $this->entityManager->flush();
        });
    }
}
