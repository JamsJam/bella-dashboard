<?php

namespace App\State\Users;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Users\CustomerMe;
use App\Entity\Users\Customers;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** @implements ProviderInterface<CustomerMe> */
final readonly class CustomerMeProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CustomerMe
    {
        $customer = $this->security->getUser();
        if (!$customer instanceof Customers || null === $customer->getId()) {
            throw new AccessDeniedHttpException('Un client authentifié est requis.');
        }

        return new CustomerMe(
            id: $customer->getId(),
            email: (string) $customer->getEmail(),
            roles: array_values($customer->getRoles()),
        );
    }
}
