<?php

namespace App\Notifier\Services;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Notifier\Notification\Notification;

final readonly class EmailNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private NotificationService $notificationService,
    ) {
    }

    public function sendEmail(string $to, string $subject, string $content, string $importance = Notification::IMPORTANCE_HIGH): void
    {
        $this->notificationService->email($to, $subject, $content, $importance);
    }

    public function sendAdminEmail(string $subject, string $content, string $importance = Notification::IMPORTANCE_HIGH): void
    {
        $this->notificationService->adminEmail($subject, $content, $importance);
    }

    public function sendTemplatedEmail(string $to, string $subject, string $template, array $context = []): void
    {
        $email = (new TemplatedEmail())
            ->from($this->notificationService->getSenderEmail())
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);

        $this->mailer->send($email);
    }
}
