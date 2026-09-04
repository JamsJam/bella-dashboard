<?php

namespace App\ApiResource\Users;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Users\CustomerMeProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/customers/me',
            security: 'is_granted("ROLE_USER")',
            provider: CustomerMeProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final readonly class CustomerMe
{
    /** @param list<string> $roles */
    public function __construct(
        public int $id,
        public string $email,
        public array $roles,
    ) {
    }
}
