<?php

namespace App\Security;

use App\Entity\Users\Customers;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class CustomerUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Customers) {
            return;
        }

        if (!$user->isSignupConfirmed()) {
            throw new CustomUserMessageAccountStatusException('Confirmez votre inscription avant de vous connecter.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
