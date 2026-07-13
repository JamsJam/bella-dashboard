<?php

namespace App\ApiResource\Auth;

final readonly class ConfirmSignupOutput
{
    public function __construct(
        public bool $isSignupConfirmed,
    ) {
    }
}
