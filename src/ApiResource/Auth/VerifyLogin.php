<?php

namespace App\ApiResource\Auth;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Auth\VerifyLoginProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/auth/verify-login',
            provider: VerifyLoginProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final class VerifyLogin
{
    public function __construct(
        public bool $isLoggedIn = false,
    ) {
    }
}
