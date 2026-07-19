<?php

namespace App\Payment\Stripe\Handler;

use App\Application\Orders\Services\CheckoutCartService;
use App\Entity\Orders\Orders;
use App\Notifier\Services\EmailNotificationService;
use App\Payment\Stripe\Client\StripeClientFactory;
use App\Payment\Stripe\Webhook\Message\StripeCheckoutSessionMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class StripeCheckoutSessionMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StripeClientFactory $stripeClientFactory,
        private EmailNotificationService $emailNotificationService,
        private CheckoutCartService $checkoutCartService,
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
            'checkout.session.expired' => $this->checkoutCartService->releaseReservation($order, Orders::STATUS_PAYMENT_EXPIRED),
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

        $processed = $this->entityManager->wrapInTransaction(function () use ($order, $message, $invoiceUrl): bool {
            $lockedOrder = $this->entityManager->getRepository(Orders::class)->findForUpdate((int) $order->getId());
            if (!$lockedOrder instanceof Orders || $lockedOrder->getStatus() !== Orders::STATUS_PENDING_PAYMENT) {
                return false;
            }

            $lockedOrder
                ->setStatus(Orders::STATUS_PAID)
                ->setStripeCheckoutSessionId($message->checkoutSessionId)
                ->setStripePaymentIntentId($message->paymentIntentId)
                ->setStripeInvoiceId($message->invoiceId)
                ->setStripeInvoiceUrl($invoiceUrl);

            $this->entityManager->flush();

            return true;
        });

        $customerEmail = $order->getCustomer()?->getEmail();
        if ($processed && $customerEmail !== null && $invoiceUrl !== null) {
            $this->emailNotificationService->sendTemplatedEmail(
                to: $customerEmail,
                subject: 'Votre facture',
                template: 'email/StripeMail.html.twig',
                context: ['invoiceUrl' => $invoiceUrl],
            );
        }
    }

}
