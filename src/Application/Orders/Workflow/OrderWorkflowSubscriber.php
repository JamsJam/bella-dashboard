<?php

namespace App\Application\Orders\Workflow;

use App\Application\Orders\Delivery\DeliveryDatePolicy;
use App\Entity\Orders\Orders;
use App\Service\LoggerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

final class OrderWorkflowSubscriber implements EventSubscriberInterface
{
    public const BLOCKER_PAYMENT_NOT_CONFIRMED = 'order_payment_not_confirmed';
    public const BLOCKER_INVOICE_MISSING = 'order_invoice_missing';
    public const BLOCKER_PAYMENT_NOT_EXPIRED = 'order_payment_not_expired';
    public const BLOCKER_NOT_GUADELOUPE = 'order_not_shipping_to_guadeloupe';
    public const BLOCKER_DELIVERY_DATE_MISSING = 'order_delivery_date_missing';
    public const BLOCKER_DELIVERY_DATE_TOO_SOON = 'order_delivery_date_too_soon';
    public const BLOCKER_GUADELOUPE_SHIPMENT = 'order_guadeloupe_requires_local_delivery';
    public const BLOCKER_TRACKING_NUMBER_MISSING = 'order_tracking_number_missing';

    public function __construct(
        private readonly LoggerService $logger,
        private readonly DeliveryDatePolicy $deliveryDatePolicy,
    ) {
    }

    public function guardProcess(GuardEvent $event): void
    {
        $order = $event->getSubject();
        if (!$order instanceof Orders) {
            return;
        }

        $reasons = [];

        if (!$order->isPaid()) {
            $message = 'Le paiement de la commande n’est pas confirmé.';
            $event->addTransitionBlocker(new TransitionBlocker(
                $message,
                self::BLOCKER_PAYMENT_NOT_CONFIRMED,
            ));
            $reasons[] = $message;
        }

        if (!$order->hasInvoice()) {
            $message = 'La facture de la commande est absente.';
            $event->addTransitionBlocker(new TransitionBlocker(
                $message,
                self::BLOCKER_INVOICE_MISSING,
            ));
            $reasons[] = $message;
        }

        if ([] !== $reasons) {
            $this->logBlockedGuard($order, OrderWorkflow::TRANSITION_PROCESS, $reasons);
        }
    }

    public function guardCancel(GuardEvent $event): void
    {
        $order = $event->getSubject();
        if (!$order instanceof Orders) {
            return;
        }

        if (Orders::STATUS_PAYMENT_EXPIRED !== $order->getStatus()) {
            $message = 'Le paiement de la commande n’est pas expiré.';
            $event->addTransitionBlocker(new TransitionBlocker(
                $message,
                self::BLOCKER_PAYMENT_NOT_EXPIRED,
            ));
            $this->logBlockedGuard($order, OrderWorkflow::TRANSITION_CANCEL, [$message]);
        }
    }

    public function guardScheduleDelivery(GuardEvent $event): void
    {
        $order = $event->getSubject();
        if (!$order instanceof Orders) {
            return;
        }

        $reasons = [];

        if (!$order->isPaid()) {
            $this->addBlocker(
                $event,
                $reasons,
                'Le paiement de la commande n’est pas confirmé.',
                self::BLOCKER_PAYMENT_NOT_CONFIRMED,
            );
        }

        if (!$order->hasInvoice()) {
            $this->addBlocker(
                $event,
                $reasons,
                'La facture de la commande est absente.',
                self::BLOCKER_INVOICE_MISSING,
            );
        }

        if (!$order->isShippingToGuadeloupe()) {
            $this->addBlocker(
                $event,
                $reasons,
                'La destination de livraison n’est pas la Guadeloupe.',
                self::BLOCKER_NOT_GUADELOUPE,
            );
        }

        if (null === $order->getDeliveryDate()) {
            $this->addBlocker(
                $event,
                $reasons,
                'La date de livraison est absente.',
                self::BLOCKER_DELIVERY_DATE_MISSING,
            );
        } elseif (!$this->deliveryDatePolicy->isAllowed($order->getDeliveryDate())) {
            $this->addBlocker(
                $event,
                $reasons,
                sprintf(
                    'La date de livraison doit être égale ou postérieure au %s.',
                    $this->deliveryDatePolicy->minimumDeliveryDate()->format('d/m/Y'),
                ),
                self::BLOCKER_DELIVERY_DATE_TOO_SOON,
            );
        }

        if ([] !== $reasons) {
            $this->logBlockedGuard($order, OrderWorkflow::TRANSITION_SCHEDULE_DELIVERY, $reasons);
        }
    }

    public function guardMarkDelivered(GuardEvent $event): void
    {
        $order = $event->getSubject();
        if (!$order instanceof Orders) {
            return;
        }

        $reasons = [];
        if (!$order->isPaid()) {
            $this->addBlocker(
                $event,
                $reasons,
                'Le paiement de la commande n’est pas confirmé.',
                self::BLOCKER_PAYMENT_NOT_CONFIRMED,
            );
        }

        if (
            \App\Enum\OrderStatus::AwaitingDelivery === $order->getOrderStatus()
            && null === $order->getDeliveryDate()
        ) {
            $this->addBlocker(
                $event,
                $reasons,
                'La date de livraison est absente.',
                self::BLOCKER_DELIVERY_DATE_MISSING,
            );
        }

        if (
            \App\Enum\OrderStatus::Shipped === $order->getOrderStatus()
            && '' === trim((string) $order->getTrackingNumber())
        ) {
            $this->addBlocker(
                $event,
                $reasons,
                'Le numéro d’expédition est absent.',
                self::BLOCKER_TRACKING_NUMBER_MISSING,
            );
        }

        if ([] !== $reasons) {
            $this->logBlockedGuard($order, OrderWorkflow::TRANSITION_MARK_DELIVERED, $reasons);
        }
    }

    public function guardShip(GuardEvent $event): void
    {
        $order = $event->getSubject();
        if (!$order instanceof Orders) {
            return;
        }

        $reasons = [];
        if (!$order->isPaid()) {
            $this->addBlocker(
                $event,
                $reasons,
                'Le paiement de la commande n’est pas confirmé.',
                self::BLOCKER_PAYMENT_NOT_CONFIRMED,
            );
        }

        if (!$order->hasInvoice()) {
            $this->addBlocker(
                $event,
                $reasons,
                'La facture de la commande est absente.',
                self::BLOCKER_INVOICE_MISSING,
            );
        }

        if (!$order->isShippingOutsideGuadeloupe()) {
            $this->addBlocker(
                $event,
                $reasons,
                'Une commande livrée en Guadeloupe doit être programmée en livraison locale.',
                self::BLOCKER_GUADELOUPE_SHIPMENT,
            );
        }

        if ('' === trim((string) $order->getTrackingNumber())) {
            $this->addBlocker(
                $event,
                $reasons,
                'Le numéro d’expédition est obligatoire.',
                self::BLOCKER_TRACKING_NUMBER_MISSING,
            );
        }

        if ([] !== $reasons) {
            $this->logBlockedGuard($order, OrderWorkflow::TRANSITION_SHIP, $reasons);
        }
    }

    public function onCompleted(CompletedEvent $event): void
    {
        $order = $event->getSubject();
        $transition = $event->getTransition();
        if (!$order instanceof Orders || null === $transition) {
            return;
        }

        $from = implode(', ', $transition->getFroms());
        $to = implode(', ', $transition->getTos());

        $this->logger->info(
            sprintf(
                'La commande %s est passée du statut %s au statut %s.',
                $this->orderIdentifier($order),
                $from,
                $to,
            ),
            [
                'order_id' => $order->getId(),
                'order_reference' => $order->getOrderReference(),
                'workflow' => OrderWorkflow::NAME,
                'transition' => $transition->getName(),
                'from_status' => $transition->getFroms(),
                'to_status' => $transition->getTos(),
            ],
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            GuardEvent::getName(OrderWorkflow::NAME, OrderWorkflow::TRANSITION_PROCESS) => 'guardProcess',
            GuardEvent::getName(OrderWorkflow::NAME, OrderWorkflow::TRANSITION_CANCEL) => 'guardCancel',
            GuardEvent::getName(OrderWorkflow::NAME, OrderWorkflow::TRANSITION_SCHEDULE_DELIVERY) => 'guardScheduleDelivery',
            GuardEvent::getName(OrderWorkflow::NAME, OrderWorkflow::TRANSITION_SHIP) => 'guardShip',
            GuardEvent::getName(OrderWorkflow::NAME, OrderWorkflow::TRANSITION_MARK_DELIVERED) => 'guardMarkDelivered',
            CompletedEvent::getName(OrderWorkflow::NAME, null) => 'onCompleted',
        ];
    }

    /**
     * @param list<string> $reasons
     */
    private function addBlocker(GuardEvent $event, array &$reasons, string $message, string $code): void
    {
        $event->addTransitionBlocker(new TransitionBlocker($message, $code));
        $reasons[] = $message;
    }

    /**
     * @param list<string> $reasons
     */
    private function logBlockedGuard(Orders $order, string $transition, array $reasons): void
    {
        $this->logger->warning(
            sprintf(
                'La transition %s de la commande %s a été bloquée : %s',
                $transition,
                $this->orderIdentifier($order),
                implode(' ', $reasons),
            ),
            [
                'order_id' => $order->getId(),
                'order_reference' => $order->getOrderReference(),
                'order_status' => $order->getOrderStatus()->value,
                'payment_status' => $order->getStatus(),
                'workflow' => OrderWorkflow::NAME,
                'transition' => $transition,
                'block_reasons' => $reasons,
            ],
        );
    }

    private function orderIdentifier(Orders $order): string
    {
        return $order->getOrderReference()
            ?? (null !== $order->getId() ? (string) $order->getId() : 'inconnue');
    }
}
