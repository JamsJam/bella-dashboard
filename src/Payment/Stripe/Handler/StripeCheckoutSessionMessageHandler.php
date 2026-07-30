<?php

namespace App\Payment\Stripe\Handler;

use App\Application\Orders\Services\CheckoutCartService;
use App\Application\Orders\Workflow\OrderWorkflow;
use App\Entity\Orders\Orders;
use App\Notifier\Services\EmailNotificationService;
use App\Payment\Stripe\Client\StripeClientFactory;
use App\Payment\Stripe\Config\StripeConfig;
use App\Payment\Stripe\Webhook\Message\StripeCheckoutSessionMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Workflow\WorkflowInterface;

#[AsMessageHandler]
final readonly class StripeCheckoutSessionMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StripeClientFactory $stripeClientFactory,
        private StripeConfig $stripeConfig,
        private EmailNotificationService $emailNotificationService,
        private CheckoutCartService $checkoutCartService,
        #[Target(OrderWorkflow::NAME)]
        private WorkflowInterface $orderWorkflow,
    ) {
    }

    public function __invoke(StripeCheckoutSessionMessage $message): void
    {
        $order = $this->entityManager->find(Orders::class, $message->orderId);
        if (!$order instanceof Orders) {
            return;
        }

        match ($message->eventType) {
            'checkout.session.completed' => $this->handleCompleted($order, $message),
            'checkout.session.expired' => $this->handleExpired($order),
            default => null,
        };
    }

    private function handleCompleted(Orders $order, StripeCheckoutSessionMessage $message): void
    {
        if ($message->paymentStatus !== 'paid') {
            return;
        }

        if ($order->getStatus() !== Orders::STATUS_PENDING_PAYMENT) {
            return;
        }

        $invoiceUrl = null;
        if ($message->invoiceId !== null) {
            $invoice = $this->stripeClientFactory->create()->invoices->retrieve($message->invoiceId);
            $invoiceUrl = is_string($invoice->hosted_invoice_url) ? $invoice->hosted_invoice_url : null;
        }

        $processedOrder = $this->entityManager->wrapInTransaction(function () use ($order, $message, $invoiceUrl): ?Orders {
            $lockedOrder = $this->entityManager->getRepository(Orders::class)->findForUpdate((int) $order->getId());
            if (!$lockedOrder instanceof Orders || $lockedOrder->getStatus() !== Orders::STATUS_PENDING_PAYMENT) {
                return null;
            }

            $lockedOrder
                ->setStatus(Orders::STATUS_PAID)
                ->setStripeCheckoutSessionId($message->checkoutSessionId)
                ->setStripePaymentIntentId($message->paymentIntentId)
                ->setStripeInvoiceId($message->invoiceId)
                ->setStripeInvoiceUrl($invoiceUrl);

            if (!$this->orderWorkflow->can($lockedOrder, OrderWorkflow::TRANSITION_PROCESS)) {
                $this->entityManager->flush();

                return null;
            }

            $this->orderWorkflow->apply($lockedOrder, OrderWorkflow::TRANSITION_PROCESS);
            $this->entityManager->flush();

            return $lockedOrder;
        });

        if (!$processedOrder instanceof Orders) {
            return;
        }

        $customerEmail = $processedOrder->getCustomer()?->getEmail();
        if ($customerEmail !== null) {
            $this->emailNotificationService->sendTemplatedEmail(
                to: $customerEmail,
                subject: sprintf('Votre commande %s est prise en compte', (string) $processedOrder->getOrderReference()),
                template: 'email/StripeMail.html.twig',
                context: [
                    'invoiceUrl' => $processedOrder->getStripeInvoiceUrl(),
                    'order' => $processedOrder,
                    'cart' => $processedOrder->getCart(),
                ],
            );
        }

        $this->emailNotificationService->sendTemplatedAdminEmail(
            subject: sprintf('Nouvelle commande %s en attente de traitement', (string) $processedOrder->getOrderReference()),
            template: 'email/order_processing_owner.html.twig',
            context: [
                'order' => $processedOrder,
                'cart' => $processedOrder->getCart(),
            ],
        );
    }

    private function handleExpired(Orders $order): void
    {
        if (!$this->checkoutCartService->releaseReservation($order, Orders::STATUS_PAYMENT_EXPIRED)) {
            return;
        }

        $customerEmail = $order->getCustomer()?->getEmail();
        if ($customerEmail === null) {
            return;
        }

        $this->emailNotificationService->sendTemplatedEmail(
            to: $customerEmail,
            subject: 'Votre panier BellaGP vous attend',
            template: 'email/checkout_expired.html.twig',
            context: [
                'order' => $order,
                'cart' => $order->getCart(),
                'cartUrl' => $this->stripeConfig->getCartUrl(),
            ],
        );
    }
}
