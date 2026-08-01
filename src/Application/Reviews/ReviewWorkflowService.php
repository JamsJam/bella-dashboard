<?php

namespace App\Application\Reviews;

use App\Entity\Reviews\Review;
use App\Notifier\Services\EmailNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;

final readonly class ReviewWorkflowService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailNotificationService $emails,
        private ClockInterface $clock,
    ) {}

    public function submit(Review $review, int $rating, string $comment): void
    {
        $review->submit($rating, $comment, $this->clock->now());
        $this->entityManager->flush();
        $this->emails->sendTemplatedAdminEmail(
            sprintf('Nouvel avis sur la commande %s', $review->getOrder()->getOrderReference()),
            'email/review_pending_owner.html.twig',
            ['review' => $review],
        );
    }

    public function moderate(Review $review, bool $accepted): void
    {
        $accepted ? $review->accept($this->clock->now()) : $review->reject($this->clock->now());
        $this->entityManager->flush();
        $email = $review->getCustomer()->getEmail();
        if ($email !== null && $email !== '') {
            $this->emails->sendTemplatedEmail(
                $email,
                $accepted ? 'Votre avis a été validé' : 'Décision concernant votre avis',
                'email/review_moderated_customer.html.twig',
                ['review' => $review],
            );
        }
    }

    public function reply(Review $review, string $reply): void
    {
        $review->updateReply($reply, $this->clock->now());
        $this->entityManager->flush();

        $email = $review->getCustomer()->getEmail();
        if ($email !== null && $email !== '') {
            $this->emails->sendTemplatedEmail(
                $email,
                'BellaGP a répondu à votre avis',
                'email/review_replied_customer.html.twig',
                ['review' => $review],
            );
        }
    }
}
