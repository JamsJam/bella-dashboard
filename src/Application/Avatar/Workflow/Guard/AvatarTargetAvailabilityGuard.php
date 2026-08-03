<?php

namespace App\Application\Avatar\Workflow\Guard;

use App\Application\Avatar\Resolver\AvatarRenameDestinationResolver;
use App\Application\Avatar\Workflow\AvatarRenameValidationContext;
use App\Application\Avatar\Workflow\AvatarRenameGuardContextStore;
use App\Entity\AvatarTemp;
use App\Application\Avatar\Model\AvatarRenameInstruction;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

#[AsEventListener(event: 'workflow.avatar_rename.guard.validate', priority: 100)]
final readonly class AvatarTargetAvailabilityGuard
{
    public const BLOCKER_INVALID_CONTEXT = 'INVALID_RENAME_CONTEXT';

    public function __construct(
        private AvatarRenameDestinationResolver $destinationResolver,
        private AvatarRenameGuardContextStore $contextStore,
    ) {
    }

    public function __invoke(GuardEvent $event): void
    {
        if (!$event->getSubject() instanceof AvatarTemp) {
            return;
        }

        $context = $this->contextStore->validation($event->getSubject());
        if (!$context instanceof AvatarRenameValidationContext) {
            $event->addTransitionBlocker(new TransitionBlocker(
                'Le contexte de validation du renommage est absent.',
                self::BLOCKER_INVALID_CONTEXT,
            ));

            return;
        }

        try {
            $message = new AvatarRenameInstruction(
                avatarTempId: (int) $event->getSubject()->getId(),
                newName: $context->newName,
                category: $context->category,
                filters: $context->filters,
            );
            $webDirectory = $this->destinationResolver->resolveWebDirectory($message);
            $destination = $this->destinationResolver->resolveAbsoluteDirectory($webDirectory).'/'.$context->newName;
            $context->recordAvailability(is_file($destination), is_file($destination) ? $webDirectory.'/'.$context->newName : null);
        } catch (\Throwable) {
            $event->addTransitionBlocker(new TransitionBlocker(
                'La destination du renommage est invalide.',
                self::BLOCKER_INVALID_CONTEXT,
            ));
        }
    }
}
