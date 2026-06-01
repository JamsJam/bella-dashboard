<?php

namespace App\Payment\Stripe\Services;

use App\Entity\Orders\Cart;
use App\Payment\Stripe\Client\StripeClientFactory;
use App\Payment\Stripe\Config\StripeConfig;
use App\Payment\Stripe\DTO\StripeCheckoutSessionResult;
use App\Payment\Stripe\Factory\StripeCheckoutLineItemsFactory;
use Doctrine\ORM\EntityManagerInterface;

final readonly class StripeCheckoutService
{
    public function __construct(
        private StripeClientFactory $stripeClientFactory,
        private StripeConfig $stripeConfig,
        private StripeCheckoutLineItemsFactory $lineItemsFactory,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function createSession(Cart $cart): StripeCheckoutSessionResult
    {
        if ($cart->getId() === null) {
            throw new \InvalidArgumentException('Cart must be persisted before creating a Stripe Checkout session.');
        }

        $session = $this->stripeClientFactory->create()->checkout->sessions->create([
            'mode' => 'payment',
            'customer_email' => $cart->getCustomer()?->getEmail(),
            'line_items' => $this->lineItemsFactory->createFromCart($cart),
            'success_url' => $this->stripeConfig->getSuccessUrl().'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->stripeConfig->getCancelUrl(),
            'invoice_creation' => [
                'enabled' => true,
            ],
            'metadata' => [
                'cart_id' => (string) $cart->getId(),
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'cart_id' => (string) $cart->getId(),
                ],
            ],
        ]);

        $cart->setStripeCheckoutSessionId($session->id);
        $this->entityManager->flush();

        return new StripeCheckoutSessionResult(
            sessionId: $session->id,
            checkoutUrl: (string) $session->url,
        );
    }
}
