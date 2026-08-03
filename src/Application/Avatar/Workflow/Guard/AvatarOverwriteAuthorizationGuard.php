<?php

namespace App\Application\Avatar\Workflow\Guard;

use App\Application\Avatar\Workflow\AvatarRenameValidationContext;
use App\Application\Avatar\Workflow\AvatarRenameGuardContextStore;
use App\Entity\AvatarTemp;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

#[AsEventListener(event: 'workflow.avatar_rename.guard.validate', priority: 0)]
final class AvatarOverwriteAuthorizationGuard
{
    public const BLOCKER_TARGET_ALREADY_EXISTS = 'TARGET_ALREADY_EXISTS';

    public function __construct(
        private readonly AvatarRenameGuardContextStore $contextStore,
    ) {
    }

    public function __invoke(GuardEvent $event): void
    {
        if (!$event->getSubject() instanceof AvatarTemp) {
            return;
        }

        $context = $this->contextStore->validation($event->getSubject());
        if (!$context instanceof AvatarRenameValidationContext || !$context->wasChecked()) {
            return;
        }

        if ($context->targetAlreadyExists() && !$context->authorization) {
            $event->addTransitionBlocker(new TransitionBlocker(
                'Une image existe déjà avec ce nom.',
                self::BLOCKER_TARGET_ALREADY_EXISTS,
            ));
        }
    }
}
