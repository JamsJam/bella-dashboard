<?php

namespace App\Controller\Webhook;

use App\Payment\Stripe\Services\StripeWebhookService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class StripeWebhookController extends AbstractController
{
    #[Route('/webhook/stripe', name: 'app_webhook_stripe', methods: ['POST'])]
    public function __invoke(Request $request, StripeWebhookService $stripeWebhookService): JsonResponse
    {
        $stripeWebhookService->handle(
            payload: $request->getContent(),
            signature: (string) $request->headers->get('Stripe-Signature', ''),
        );

        return $this->json(['received' => true]);
    }
}
