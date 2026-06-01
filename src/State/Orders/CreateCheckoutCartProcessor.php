<?php

namespace App\State\Orders;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Orders\CheckoutCartInput;
use App\ApiResource\Orders\CheckoutCartOutput;
use App\Application\Orders\Services\CheckoutCartService;
use App\Entity\Users\Customers;
use App\Payment\Stripe\Services\StripeCheckoutService;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<CheckoutCartInput, CheckoutCartOutput>
 */
final readonly class CreateCheckoutCartProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private CheckoutCartService $checkoutCartService,
        private StripeCheckoutService $stripeCheckoutService,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CheckoutCartOutput
    {
        if (!$data instanceof CheckoutCartInput) {
            throw new \InvalidArgumentException('Invalid checkout cart payload.');
        }

        $customer = $this->security->getUser();
        if (!$customer instanceof Customers) {
            throw new \RuntimeException('A connected customer is required to create a checkout cart.');
        }

        $cart = $this->checkoutCartService->createPendingCart($data, $customer);
        $stripeSession = $this->stripeCheckoutService->createSession($cart);

        return new CheckoutCartOutput(
            cartId: (int) $cart->getId(),
            checkoutSessionId: $stripeSession->sessionId,
            checkoutUrl: $stripeSession->checkoutUrl,
        );
    }
}
