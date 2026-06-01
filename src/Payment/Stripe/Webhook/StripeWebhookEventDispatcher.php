<?php

namespace App\Payment\Stripe\Webhook;

use App\Payment\Stripe\Config\StripeConfig;
use App\Payment\Stripe\Webhook\Message\StripeCheckoutSessionMessage;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class StripeWebhookEventDispatcher
{
    private const SUPPORTED_EVENTS = [
        'checkout.session.completed',
        'checkout.session.expired',
        'checkout.session.async_payment_failed',
    ];

    public function __construct(
        private StripeConfig $stripeConfig,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function dispatchFromPayload(string $payload, string $signature): void
    {
        try {
            $event = Webhook::constructEvent($payload, $signature, $this->stripeConfig->getWebhookSecret());
        } catch (SignatureVerificationException|\UnexpectedValueException $exception) {
            throw new BadRequestHttpException('Invalid Stripe webhook signature.', $exception);
        }

        if (!in_array($event->type, self::SUPPORTED_EVENTS, true)) {
            return;
        }

        $session = $event->data->object;
        if (!$session instanceof Session) {
            return;
        }

        $cartId = filter_var($session->metadata['cart_id'] ?? null, FILTER_VALIDATE_INT);
        if ($cartId === false || $cartId <= 0) {
            throw new \InvalidArgumentException('Stripe webhook missing cart_id metadata.');
        }

        $this->messageBus->dispatch(new StripeCheckoutSessionMessage(
            eventId: $event->id,
            eventType: $event->type,
            cartId: $cartId,
            checkoutSessionId: $session->id,
            paymentIntentId: is_string($session->payment_intent) ? $session->payment_intent : null,
            invoiceId: is_string($session->invoice) ? $session->invoice : null,
        ));
    }
}
