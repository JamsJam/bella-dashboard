<?php

namespace App\Application\Orders\Delivery;

use App\Application\Orders\Workflow\OrderWorkflow;
use App\Application\Reviews\ReviewRequestService;
use App\Entity\Orders\Orders;
use App\Notifier\Services\EmailNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final readonly class MarkOrderDeliveredService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailNotificationService $emailNotificationService,
        private ClockInterface $clock,
        private ReviewRequestService $reviewRequestService,
        #[Target(OrderWorkflow::NAME)]
        private WorkflowInterface $orderWorkflow,
    ) {
    }

    public function mark(Orders $order): bool
    {
        if (!$this->orderWorkflow->can($order, OrderWorkflow::TRANSITION_MARK_DELIVERED)) {
            return false;
        }

        $order->setDeliveredAt($this->clock->now());
        $this->orderWorkflow->apply($order, OrderWorkflow::TRANSITION_MARK_DELIVERED);
        $this->entityManager->flush();

        $reviews = $this->reviewRequestService->createForOrder($order);

        $customerEmail = $order->getCustomer()?->getEmail();
        if ($customerEmail !== null && $customerEmail !== '') {
            $this->emailNotificationService->sendTemplatedEmail(
                to: $customerEmail,
                subject: sprintf('Votre commande %s a été livrée', (string) $order->getOrderReference()),
                template: 'email/order_delivered_customer.html.twig',
                context: ['order' => $order, 'reviews' => $reviews],
            );
        }

        return true;
    }
}
