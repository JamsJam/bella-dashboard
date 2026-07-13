<?php

namespace App\ApiResource\Auth;

final readonly class SignupOutput
{
    public function __construct(
        public int $id,
        public string $email,
        public bool $isSignupConfirmed,
        public ?\DateTimeImmutable $signupVerificationExpiresAt = null,
    ) {
    }
}
