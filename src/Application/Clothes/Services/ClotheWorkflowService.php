<?php

namespace App\Application\Clothes\Services;

use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Enum\ClotheStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final readonly class ClotheWorkflowService
{
    public function __construct(
        private WorkflowInterface $clothePublicationStateMachine,
        private ClotheCompletenessChecker $completenessChecker,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function apply(ClothesVariant $variant, string $transition, ?\DateTimeImmutable $scheduledAt = null): void
    {
        if ($transition === 'programmer_publication') {
            if (!$scheduledAt instanceof \DateTimeImmutable || $scheduledAt <= new \DateTimeImmutable()) {
                throw new \DomainException('La date de publication doit être future.');
            }
            $variant->setScheduledPublicationAt($scheduledAt);
        }

        if (in_array($transition, ['rendre_publiable', 'publier', 'publier_automatiquement', 'remettre_en_ligne'], true)) {
            $result = $this->completenessChecker->checkVariant($variant);
            if (!$result->isComplete()) {
                throw new \DomainException(implode(' ', $result->errors()));
            }
        }

        if (!$this->clothePublicationStateMachine->can($variant, $transition)) {
            throw new \DomainException(sprintf('La transition "%s" est impossible depuis l’état %s.', $transition, $variant->getPublicationStatus()->label()));
        }

        $this->clothePublicationStateMachine->apply($variant, $transition);
        $now = new \DateTimeImmutable();

        if (in_array($transition, ['publier', 'publier_automatiquement', 'remettre_en_ligne'], true)) {
            $variant->setPublishedAt($now)->setScheduledPublicationAt(null);
        }
        if (in_array($transition, ['annuler_programmation', 'invalider_programmation'], true)) {
            $variant->setScheduledPublicationAt(null);
        }
        if (str_starts_with($transition, 'archiver_')) {
            $variant->setArchivedAt($now)->setScheduledPublicationAt(null);
        }
        if ($transition === 'restaurer') {
            $variant->setArchivedAt(null);
        }

        $variant->setEditedAt($now);
        $this->entityManager->flush();
    }

    public function reconcileCompleteness(Clothes $clothe): void
    {
        foreach ($clothe->getVariants() as $variant) {
            $this->reconcileVariant($variant);
        }
    }

    public function reconcileVariant(ClothesVariant $variant): bool
    {
        $complete = $this->completenessChecker->checkVariant($variant)->isComplete();
        $transition = match ($variant->getPublicationStatus()) {
            ClotheStatus::Draft => $complete ? 'rendre_publiable' : null,
            ClotheStatus::Publishable => $complete ? null : 'repasser_en_brouillon',
            ClotheStatus::Scheduled => $complete ? null : 'invalider_programmation',
            default => null,
        };

        if ($transition === null) {
            return false;
        }

        $this->apply($variant, $transition);

        return true;
    }
}
