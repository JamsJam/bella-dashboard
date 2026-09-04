<?php

namespace App\Tests\Application\Orders\Workflow;

use App\Application\Orders\Workflow\OrderWorkflow;
use App\Application\Orders\Workflow\OrderWorkflowSubscriber;
use App\Entity\Orders\Orders;
use App\Enum\OrderStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

final class OrderWorkflowTest extends KernelTestCase
{
    private WorkflowInterface $workflow;

    protected function setUp(): void
    {
        self::bootKernel();
        $workflow = self::getContainer()->get('state_machine.' . OrderWorkflow::NAME);
        self::assertInstanceOf(WorkflowInterface::class, $workflow);
        $this->workflow = $workflow;
    }

    public function testPaidOrderWithInvoiceCanBeProcessed(): void
    {
        $order = $this->paidOrderWithInvoice();

        self::assertTrue($this->workflow->can($order, OrderWorkflow::TRANSITION_PROCESS));

        $this->workflow->apply($order, OrderWorkflow::TRANSITION_PROCESS);

        self::assertSame(OrderStatus::Processing, $order->getOrderStatus());
        self::assertFalse($this->workflow->can($order, OrderWorkflow::TRANSITION_PROCESS));
    }

    public function testUnpaidOrderCannotBeProcessed(): void
    {
        $order = $this->paidOrderWithInvoice()
            ->setStatus(Orders::STATUS_PENDING_PAYMENT);

        self::assertFalse($this->workflow->can($order, OrderWorkflow::TRANSITION_PROCESS));
        self::assertTrue(
            $this->workflow
                ->buildTransitionBlockerList($order, OrderWorkflow::TRANSITION_PROCESS)
                ->has(OrderWorkflowSubscriber::BLOCKER_PAYMENT_NOT_CONFIRMED),
        );
        self::assertSame(OrderStatus::Created, $order->getOrderStatus());
    }

    public function testOrderWithoutInvoiceCannotBeProcessed(): void
    {
        $order = (new Orders())
            ->setStatus(Orders::STATUS_PAID);

        self::assertFalse($this->workflow->can($order, OrderWorkflow::TRANSITION_PROCESS));
        self::assertTrue(
            $this->workflow
                ->buildTransitionBlockerList($order, OrderWorkflow::TRANSITION_PROCESS)
                ->has(OrderWorkflowSubscriber::BLOCKER_INVOICE_MISSING),
        );
        self::assertSame(OrderStatus::Created, $order->getOrderStatus());
    }

    public function testCreatedOrderCanBeCancelled(): void
    {
        $order = (new Orders())
            ->setStatus(Orders::STATUS_PAYMENT_EXPIRED);

        self::assertTrue($this->workflow->can($order, OrderWorkflow::TRANSITION_CANCEL));

        $this->workflow->apply($order, OrderWorkflow::TRANSITION_CANCEL);

        self::assertSame(OrderStatus::Cancelled, $order->getOrderStatus());
        self::assertFalse($this->workflow->can($order, OrderWorkflow::TRANSITION_CANCEL));
        self::assertFalse($this->workflow->can($order, OrderWorkflow::TRANSITION_PROCESS));
    }

    public function testPendingPaymentOrderCannotBeCancelled(): void
    {
        $order = (new Orders())
            ->setStatus(Orders::STATUS_PENDING_PAYMENT);

        $blockers = $this->workflow->buildTransitionBlockerList($order, OrderWorkflow::TRANSITION_CANCEL);

        self::assertTrue($blockers->has(OrderWorkflowSubscriber::BLOCKER_PAYMENT_NOT_EXPIRED));
        self::assertFalse($this->workflow->can($order, OrderWorkflow::TRANSITION_CANCEL));
        self::assertSame(OrderStatus::Created, $order->getOrderStatus());
    }

    public function testProcessingPaidGuadeloupeOrderCanScheduleDelivery(): void
    {
        $order = $this->paidOrderWithInvoice()
            ->setOrderStatus(OrderStatus::Processing)
            ->setShippinfo(['destination' => 'Guadeloupe'])
            ->setDeliveryDate((new \DateTimeImmutable('today', new \DateTimeZone('Europe/Paris')))->modify('+2 days'));

        self::assertTrue($this->workflow->can($order, OrderWorkflow::TRANSITION_SCHEDULE_DELIVERY));

        $this->workflow->apply($order, OrderWorkflow::TRANSITION_SCHEDULE_DELIVERY);

        self::assertSame(OrderStatus::AwaitingDelivery, $order->getOrderStatus());
    }

    public function testDeliveryCannotBeScheduledOutsideGuadeloupe(): void
    {
        $order = $this->paidOrderWithInvoice()
            ->setOrderStatus(OrderStatus::Processing)
            ->setShippinfo(['destination' => 'France hexagonale'])
            ->setDeliveryDate((new \DateTimeImmutable('today', new \DateTimeZone('Europe/Paris')))->modify('+2 days'));

        $blockers = $this->workflow->buildTransitionBlockerList(
            $order,
            OrderWorkflow::TRANSITION_SCHEDULE_DELIVERY,
        );

        self::assertTrue($blockers->has(OrderWorkflowSubscriber::BLOCKER_NOT_GUADELOUPE));
        self::assertFalse($this->workflow->can($order, OrderWorkflow::TRANSITION_SCHEDULE_DELIVERY));
    }

    public function testDeliveryDateMustBeAtLeastTwoDaysAhead(): void
    {
        $order = $this->paidOrderWithInvoice()
            ->setOrderStatus(OrderStatus::Processing)
            ->setShippinfo(['destination' => 'Guadeloupe'])
            ->setDeliveryDate((new \DateTimeImmutable('today', new \DateTimeZone('Europe/Paris')))->modify('+1 day'));

        $blockers = $this->workflow->buildTransitionBlockerList(
            $order,
            OrderWorkflow::TRANSITION_SCHEDULE_DELIVERY,
        );

        self::assertTrue($blockers->has(OrderWorkflowSubscriber::BLOCKER_DELIVERY_DATE_TOO_SOON));
        self::assertFalse($this->workflow->can($order, OrderWorkflow::TRANSITION_SCHEDULE_DELIVERY));
    }

    public function testPaidScheduledOrderCanBeMarkedAsDelivered(): void
    {
        $order = $this->paidOrderWithInvoice()
            ->setOrderStatus(OrderStatus::AwaitingDelivery)
            ->setDeliveryDate(new \DateTimeImmutable('today', new \DateTimeZone('Europe/Paris')));

        self::assertTrue($this->workflow->can($order, OrderWorkflow::TRANSITION_MARK_DELIVERED));

        $this->workflow->apply($order, OrderWorkflow::TRANSITION_MARK_DELIVERED);

        self::assertSame(OrderStatus::Delivered, $order->getOrderStatus());
        self::assertFalse($this->workflow->can($order, OrderWorkflow::TRANSITION_MARK_DELIVERED));
    }

    public function testUnpaidOrderCannotBeMarkedAsDelivered(): void
    {
        $order = $this->paidOrderWithInvoice()
            ->setStatus(Orders::STATUS_PENDING_PAYMENT)
            ->setOrderStatus(OrderStatus::AwaitingDelivery)
            ->setDeliveryDate(new \DateTimeImmutable('today', new \DateTimeZone('Europe/Paris')));

        $blockers = $this->workflow->buildTransitionBlockerList(
            $order,
            OrderWorkflow::TRANSITION_MARK_DELIVERED,
        );

        self::assertTrue($blockers->has(OrderWorkflowSubscriber::BLOCKER_PAYMENT_NOT_CONFIRMED));
        self::assertFalse($this->workflow->can($order, OrderWorkflow::TRANSITION_MARK_DELIVERED));
    }

    public function testPaidOrderOutsideGuadeloupeCanBeShippedWithTrackingNumber(): void
    {
        $order = $this->paidOrderWithInvoice()
            ->setOrderStatus(OrderStatus::Processing)
            ->setShippinfo(['destination' => 'France hexagonale'])
            ->setTrackingNumber('TRACK-123');

        self::assertTrue($this->workflow->can($order, OrderWorkflow::TRANSITION_SHIP));

        $this->workflow->apply($order, OrderWorkflow::TRANSITION_SHIP);

        self::assertSame(OrderStatus::Shipped, $order->getOrderStatus());
    }

    public function testGuadeloupeOrderCannotBeShipped(): void
    {
        $order = $this->paidOrderWithInvoice()
            ->setOrderStatus(OrderStatus::Processing)
            ->setShippinfo(['destination' => 'Guadeloupe'])
            ->setTrackingNumber('TRACK-123');

        $blockers = $this->workflow->buildTransitionBlockerList($order, OrderWorkflow::TRANSITION_SHIP);

        self::assertTrue($blockers->has(OrderWorkflowSubscriber::BLOCKER_GUADELOUPE_SHIPMENT));
        self::assertFalse($this->workflow->can($order, OrderWorkflow::TRANSITION_SHIP));
    }

    public function testShippedOrderCanBeManuallyMarkedAsDelivered(): void
    {
        $order = $this->paidOrderWithInvoice()
            ->setOrderStatus(OrderStatus::Shipped)
            ->setTrackingNumber('TRACK-123');

        self::assertTrue($this->workflow->can($order, OrderWorkflow::TRANSITION_MARK_DELIVERED));

        $this->workflow->apply($order, OrderWorkflow::TRANSITION_MARK_DELIVERED);

        self::assertSame(OrderStatus::Delivered, $order->getOrderStatus());
    }

    private function paidOrderWithInvoice(): Orders
    {
        return (new Orders())
            ->setStatus(Orders::STATUS_PAID)
            ->setStripeInvoiceId('in_test')
            ->setStripeInvoiceUrl('https://invoice.example.test/in_test');
    }
}
