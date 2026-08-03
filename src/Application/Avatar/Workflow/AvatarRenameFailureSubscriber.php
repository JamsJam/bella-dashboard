<?php

namespace App\Application\Avatar\Workflow;

use App\Entity\AvatarTemp;
use App\Message\Avatar\RenameAvatarMessage;
use App\Service\LoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AvatarRenameFailureSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        #[Autowire(service: 'state_machine.avatar_rename')]
        private WorkflowInterface $workflow,
        private LoggerService $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // The retry listener runs at priority 100 and sets willRetry() first.
        return [WorkerMessageFailedEvent::class => ['onMessageFailed', 0]];
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();
        if (!$message instanceof RenameAvatarMessage || $event->willRetry()) {
            return;
        }

        $manager = $this->managerRegistry->getManagerForClass(AvatarTemp::class);
        if (!$manager instanceof EntityManagerInterface) {
            return;
        }

        if (!$manager->isOpen()) {
            $manager = $this->managerRegistry->resetManager();
        } else {
            $manager->clear();
        }

        if (!$manager instanceof EntityManagerInterface) {
            return;
        }

        $avatarTemp = $manager->find(AvatarTemp::class, $message->avatarTempId);
        if (!$avatarTemp instanceof AvatarTemp || $avatarTemp->getStatus() !== AvatarRenameWorkflow::PLACE_RENAMING) {
            return;
        }

        $this->workflow->apply($avatarTemp, AvatarRenameWorkflow::TRANSITION_FAIL);
        $manager->flush();
        $this->logger->exception($event->getThrowable(), 'Avatar rename definitively failed.', [
            'avatar_temp_id' => $message->avatarTempId,
        ]);
    }
}
