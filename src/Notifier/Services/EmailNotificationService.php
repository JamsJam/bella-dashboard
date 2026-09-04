<?php

namespace App\Notifier\Services;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Notifier\Notification\Notification;

final readonly class EmailNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private NotificationService $notificationService,
        private LoggerInterface $logger,
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
        $senderEmail = $this->notificationService->getSenderEmail();
        $this->logger->debug('Resolved notification sender email.', [
            'sender_email' => $senderEmail,
            'recipient' => $to,
            'subject' => $subject,
            'template' => $template,
        ]);

        $email = (new TemplatedEmail())
            ->from($senderEmail)
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);

        $this->debugTemplatedEmail($email);

        $this->mailer->send($email);
    }

    public function sendTemplatedAdminEmail(string $subject, string $template, array $context = []): void
    {
        $this->sendTemplatedEmail(
            $this->notificationService->getAdminEmail(),
            $subject,
            $template,
            $context,
        );
    }

    public function sendTemplatedAdminEmailWithAttachment(
        string $subject,
        string $template,
        array $context,
        string $attachment,
        string $filename,
        string $contentType,
    ): void {
        $email = (new TemplatedEmail())
            ->from($this->notificationService->getSenderEmail())
            ->to($this->notificationService->getAdminEmail())
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context)
            ->attach($attachment, $filename, $contentType);

        $this->debugTemplatedEmail($email);
        $this->mailer->send($email);
    }

    private function debugTemplatedEmail(TemplatedEmail $email): void
    {
        $this->logger->error('Templated email ready to send.', [
            'from' => $this->formatAddresses($email->getFrom()),
            'to' => $this->formatAddresses($email->getTo()),
            'subject' => $email->getSubject(),
            'html_template' => $email->getHtmlTemplate(),
            'context_keys' => array_keys($email->getContext()),
        ]);
    }

    /**
     * @param list<Address> $addresses
     *
     * @return list<string>
     */
    private function formatAddresses(array $addresses): array
    {
        return array_map(
            static fn (Address $address): string => $address->toString(),
            $addresses,
        );
    }
}
