<?php

namespace App\Notifier\Services;

use App\Application\Config\Service\ContactConfigService;
use App\Notifier\Notification\SimpleEmailNotification;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;

final readonly class NotificationService
{
    public function __construct(
        private NotifierInterface $notifier,
        private ContactConfigService $contactConfigService,
        private FlashService $flashService,
    ) {
    }

    public function email(
        string $to,
        string $subject,
        string $content,
        string $importance = Notification::IMPORTANCE_HIGH,
    ): void {
        $this->notifier->send(
            new SimpleEmailNotification($subject, $content, $this->getSenderEmail(), $importance),
            new Recipient($to),
        );
    }

    public function adminEmail(
        string $subject,
        string $content,
        string $importance = Notification::IMPORTANCE_HIGH,
    ): void {
        $this->email($this->getAdminEmail(), $subject, $content, $importance);
    }

    public function getSenderEmail(): string
    {
        $this->assertContactConfigurationExists();

        $config = $this->contactConfigService->get();
        $email = '' !== $config->ownerEmail ? $config->ownerEmail : $config->developerContact->email;

        if ('' === $email) {
            $this->flashService->warning('Configure l’application avant d’envoyer des notifications.');
            throw new \RuntimeException('Aucun email expediteur configure dans la configuration de contact.');
        }

        return $email;
    }

    public function getAdminEmail(): string
    {
        $this->assertContactConfigurationExists();

        $config = $this->contactConfigService->get();
        $email = '' !== $config->ownerEmail ? $config->ownerEmail : $config->developerContact->email;

        if ('' === $email) {
            $this->flashService->warning('Configure l’application avant d’envoyer des notifications.');
            throw new \RuntimeException('Aucun email administrateur configure dans la configuration de contact.');
        }

        return $email;
    }

    private function assertContactConfigurationExists(): void
    {
        if ($this->contactConfigService->exists()) {
            return;
        }

        $this->flashService->warning('Configure l’application avant d’envoyer des notifications.');

        throw new \RuntimeException('La configuration de contact est introuvable.');
    }
}
