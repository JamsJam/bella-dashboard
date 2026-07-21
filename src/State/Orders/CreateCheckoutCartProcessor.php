<?php

namespace App\State\Orders;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Orders\CheckoutCartInput;
use App\ApiResource\Orders\CheckoutCartOutput;
use App\Application\Orders\Services\CheckoutCartService;
use App\Application\Orders\Exception\InvalidCheckoutRequestException;
use App\Entity\Orders\Orders;
use App\Entity\Users\Customers;
use App\Payment\Stripe\Services\StripeCheckoutService;
use Symfony\Bundle\SecurityBundle\Security;
use Stripe\Exception\InvalidRequestException;

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
            throw new InvalidCheckoutRequestException('Le corps de la requête checkout est absent ou ne respecte pas le format attendu.');
        }

        $customer = $this->security->getUser();
        if (!$customer instanceof Customers) {
            throw new \RuntimeException('A connected customer is required to create a checkout cart.');
        }

        $order = $this->checkoutCartService->createPendingOrder($data, $customer);

        try {
            $stripeSession = $this->stripeCheckoutService->createSession($order);
        } catch (\Throwable $exception) {
            $this->checkoutCartService->releaseReservation($order, Orders::STATUS_CHECKOUT_CREATION_FAILED);

            if ($exception instanceof InvalidRequestException) {
                throw new InvalidCheckoutRequestException($this->stripeErrorMessage($exception), previous: $exception);
            }

            throw $exception;
        }

        return new CheckoutCartOutput(
            cartId: (int) $order->getCart()?->getId(),
            orderId: (int) $order->getId(),
            orderReference: (string) $order->getOrderReference(),
            checkoutSessionId: $stripeSession->sessionId,
            checkoutUrl: $stripeSession->checkoutUrl,
        );
    }

    private function stripeErrorMessage(InvalidRequestException $exception): string
    {
        $details = ['Stripe a refusé la création de la session Checkout : '.$exception->getMessage()];

        if ($exception->getStripeParam()) {
            $details[] = 'Paramètre concerné : '.$exception->getStripeParam();
        }
        if ($exception->getStripeCode()) {
            $details[] = 'Code Stripe : '.$exception->getStripeCode();
        }
        if ($exception->getRequestId()) {
            $details[] = 'Identifiant de requête Stripe : '.$exception->getRequestId();
        }

        return implode(' ', $details);
    }
}
