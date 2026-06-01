<?php

namespace App\Notifier\Services;

use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class FlashService
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function add(string $type, string $message): void
    {
        try {
            $this->requestStack->getSession()->getFlashBag()->add($type, $message);
        } catch (SessionNotFoundException) {
            return;
        }
    }

    public function success(string $message): void
    {
        $this->add('success', $message);
    }

    public function error(string $message): void
    {
        $this->add('error', $message);
    }

    public function warning(string $message): void
    {
        $this->add('warning', $message);
    }

    public function info(string $message): void
    {
        $this->add('info', $message);
    }
}
