<?php

namespace App\State\Auth;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Auth\VerifyLogin;
use App\Entity\Users\Customers;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProviderInterface<VerifyLogin>
 */
final readonly class VerifyLoginProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): VerifyLogin
    {
        return new VerifyLogin($this->security->getUser() instanceof Customers);
    }
}
