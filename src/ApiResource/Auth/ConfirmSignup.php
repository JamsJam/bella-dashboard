<?php

namespace App\ApiResource\Auth;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\Auth\ConfirmSignupProcessor;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/auth/verify-confirmation-code',
            input: ConfirmSignupInput::class,
            output: ConfirmSignupOutput::class,
            deserialize: false,
            processor: ConfirmSignupProcessor::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final class ConfirmSignup
{
}
