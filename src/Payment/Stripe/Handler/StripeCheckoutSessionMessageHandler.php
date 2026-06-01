<?php

namespace App\Payment\Stripe\Handler;

use App\Entity\Orders\Cart;
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
    ) {
    }

    public function __invoke(StripeCheckoutSessionMessage $message): void
    {
        $cart = $this->entityManager->find(Cart::class, $message->cartId);
        if (!$cart instanceof Cart) {
            return;
        }

        match ($message->eventType) {
            'checkout.session.completed' => $this->handleCompleted($cart, $message),
            'checkout.session.expired' => $this->markCart($cart, Cart::STATUS_PAYMENT_EXPIRED),
            'checkout.session.async_payment_failed' => $this->markCart($cart, Cart::STATUS_PAYMENT_FAILED),
            default => null,
        };
    }

    private function handleCompleted(Cart $cart, StripeCheckoutSessionMessage $message): void
    {
        if ($cart->getStatus() === Cart::STATUS_PAID && $cart->getOrder() instanceof Orders) {
            return;
        }

        $invoiceUrl = null;
        if ($message->invoiceId !== null) {
            $invoice = $this->stripeClientFactory->create()->invoices->retrieve($message->invoiceId);
            $invoiceUrl = is_string($invoice->hosted_invoice_url) ? $invoice->hosted_invoice_url : null;
            $cart->setStripeInvoiceId($message->invoiceId);
            $cart->setStripeInvoiceUrl($invoiceUrl);
        }

        $cart
            ->setStatus(Cart::STATUS_PAID)
            ->setStripeCheckoutSessionId($message->checkoutSessionId)
            ->setStripePaymentIntentId($message->paymentIntentId);

        if (!$cart->getOrder() instanceof Orders) {
            $this->entityManager->persist($this->createOrderFromCart($cart));
        }

        $this->entityManager->flush();

        $customerEmail = $cart->getCustomer()?->getEmail();
        if ($customerEmail !== null && $invoiceUrl !== null) {
            $this->emailNotificationService->sendTemplatedEmail(
                to: $customerEmail,
                subject: 'Votre facture',
                template: 'email/StripeMail.html.twig',
                context: ['invoiceUrl' => $invoiceUrl],
            );
        }
    }

    private function markCart(Cart $cart, string $status): void
    {
        if ($cart->getStatus() !== Cart::STATUS_PAID) {
            $cart->setStatus($status);
            $this->entityManager->flush();
        }
    }

    private function createOrderFromCart(Cart $cart): Orders
    {
        $order = (new Orders())
            ->setCart($cart)
            ->setCustomer($cart->getCustomer())
            ->setSubtotal($cart->getSubtotal())
            ->setTotal($cart->getTotal())
            ->setStatus('paid')
            ->setOrderReference($this->createOrderReference($cart))
            ->setFees(0)
            ->setShippinfo([])
            ->setTva(0);

        if (method_exists($order, 'setCreatedAt')) {
            $order->setCreatedAt(new \DateTimeImmutable());
        }

        if (method_exists($order, 'setEditedAt')) {
            $order->setEditedAt(new \DateTimeImmutable());
        }

        return $order;
    }

    private function createOrderReference(Cart $cart): string
    {
        return sprintf('ORDER-%s-%06d', (new \DateTimeImmutable())->format('Ymd'), (int) $cart->getId());
    }
}
