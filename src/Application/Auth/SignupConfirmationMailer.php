<?php

namespace App\Application\Auth;

use App\Entity\Users\Customers;
use App\Notifier\Services\EmailNotificationService;

final readonly class SignupConfirmationMailer
{
    public function __construct(
        private EmailNotificationService $emailNotificationService,
    ) {
    }

    public function sendConfirmationCode(Customers $customer): void
    {
        $email = $customer->getEmail();
        $code = $customer->getSignupVerificationCode();

        if ($email === null || $code === null) {
            throw new \InvalidArgumentException('Customer email and signup confirmation code are required.');
        }

        $this->emailNotificationService->sendTemplatedEmail(
            to: $email,
            subject: 'Confirmez votre inscription',
            template: 'email/signup_confirmation.html.twig',
            context: [
                'code' => $code,
                'expiresAt' => $customer->getSignupVerificationExpiresAt(),
            ],
        );
    }
}
