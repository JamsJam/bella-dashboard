<?php

namespace App\ApiResource\Auth;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\Auth\SignupProcessor;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/auth/signup',
            input: SignupInput::class,
            output: SignupOutput::class,
            processor: SignupProcessor::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final class Signup
{
}
