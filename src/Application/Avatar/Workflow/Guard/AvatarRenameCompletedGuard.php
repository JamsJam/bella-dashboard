<?php

namespace App\Application\Avatar\Workflow\Guard;

use App\Application\Avatar\Workflow\AvatarRenameCompletionContext;
use App\Application\Avatar\Workflow\AvatarRenameGuardContextStore;
use App\Entity\AvatarTemp;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

#[AsEventListener(event: 'workflow.avatar_rename.guard.mark_renamed')]
final class AvatarRenameCompletedGuard
{
    public const BLOCKER_INVALID_RESULT = 'INVALID_RENAME_RESULT';

    public function __construct(
        private readonly AvatarRenameGuardContextStore $contextStore,
    ) {
    }

    public function __invoke(GuardEvent $event): void
    {
        $avatarTemp = $event->getSubject();
        $context = $avatarTemp instanceof AvatarTemp ? $this->contextStore->completion($avatarTemp) : null;

        if (!$avatarTemp instanceof AvatarTemp || !$context instanceof AvatarRenameCompletionContext) {
            $this->block($event, 'Le résultat du renommage ne peut pas être vérifié.');

            return;
        }

        if (!is_file($context->destinationPath) || basename($context->destinationPath) !== $context->expectedName) {
            $this->block($event, 'Le fichier renommé est absent ou porte un nom incorrect.');

            return;
        }

        $checksum = hash_file('sha256', $context->destinationPath);
        if (false === $checksum || !hash_equals($context->expectedChecksum, $checksum)) {
            $this->block($event, 'Le checksum du fichier renommé est incorrect.');

            return;
        }

        $referencedPath = null;
        if (method_exists($context->avatarPart, 'getImage')) {
            $referencedPath = $context->avatarPart->getImage();
        } elseif (method_exists($context->avatarPart, 'getImages')) {
            $images = $context->avatarPart->getImages();
            $referencedPath = is_array($images) && in_array($context->imageWebPath, $images, true)
                ? $context->imageWebPath
                : null;
        }

        if ($referencedPath !== $context->imageWebPath) {
            $this->block($event, 'L’entité avatar ne référence pas le fichier renommé.');
        }
    }

    private function block(GuardEvent $event, string $message): void
    {
        $event->addTransitionBlocker(new TransitionBlocker($message, self::BLOCKER_INVALID_RESULT));
    }
}
