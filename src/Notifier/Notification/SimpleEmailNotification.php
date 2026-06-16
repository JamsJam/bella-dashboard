<?php

namespace App\Notifier\Notification;

use Symfony\Component\Mime\Email;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;

final class SimpleEmailNotification extends Notification implements EmailNotificationInterface
{
    public function __construct(
        string $subject,
        string $content,
        private readonly string $from,
        string $importance = self::IMPORTANCE_HIGH,
    ) {
        parent::__construct($subject, ['email']);

        $this
            ->content($content)
            ->importance($importance);
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, ?string $transport = null): ?EmailMessage
    {
        $email = (new Email())
            ->from($this->from)
            ->to($recipient->getEmail())
            ->subject($this->getSubject())
            ->text($this->getContent() ?: $this->getSubject())
            ->html(nl2br(htmlspecialchars($this->getContent() ?: $this->getSubject(), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8')));

        return (new EmailMessage($email))->transport($transport);
    }
}
