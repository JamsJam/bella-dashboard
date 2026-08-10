<?php

namespace App\Application\Clothes\EventListener;

use App\Application\Clothes\Services\Clothe\ClotheWorkflowService;
use App\Entity\Clothes\ClothesVariant;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
final readonly class ClothesVariantPublicationStatusListener
{
    public function __construct(
        private ClotheWorkflowService $workflowService,
    ) {
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $entityManager = $event->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $metadata = $entityManager->getClassMetadata(ClothesVariant::class);

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof ClothesVariant) {
                continue;
            }

            if ($this->workflowService->reconcilePublicationEligibilityWithoutFlush($entity)) {
                $unitOfWork->recomputeSingleEntityChangeSet($metadata, $entity);
            }
        }
    }
}
