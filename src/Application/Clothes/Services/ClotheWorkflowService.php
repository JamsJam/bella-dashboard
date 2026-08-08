<?php

namespace App\Application\Clothes\Services;

use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Enum\ClotheStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final readonly class ClotheWorkflowService
{
    private const STORAGE_TIMEZONE = 'UTC';

    public function __construct(
        private WorkflowInterface $clothePublicationStateMachine,
        private ClotheCompletenessChecker $completenessChecker,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function apply(ClothesVariant $variant, string $transition, ?\DateTimeImmutable $scheduledAt = null): void
    {
        $this->applyWithoutFlush($variant, $transition, $scheduledAt);
        $this->entityManager->flush();
    }

    private function applyWithoutFlush(
        ClothesVariant $variant,
        string $transition,
        ?\DateTimeImmutable $scheduledAt = null,
    ): void {
        if ('programmer_publication' === $transition) {
            if (!$scheduledAt instanceof \DateTimeImmutable || $scheduledAt <= $this->nowUtc()) {
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
        $now = $this->nowUtc();

        if (in_array($transition, ['publier', 'publier_automatiquement', 'remettre_en_ligne'], true)) {
            $variant->setPublishedAt($now)->setScheduledPublicationAt(null);
        }
        if (in_array($transition, ['annuler_programmation', 'invalider_programmation'], true)) {
            $variant->setScheduledPublicationAt(null);
        }
        if (str_starts_with($transition, 'archiver_')) {
            $variant->setArchivedAt($now)->setScheduledPublicationAt(null);
        }
        if ('restaurer' === $transition) {
            $variant->setArchivedAt(null);
        }

        $variant->setEditedAt($now);
    }

    /**
     * @param list<ClothesVariant> $variants
     */
    public function scheduleAll(array $variants, \DateTimeImmutable $scheduledAt): void
    {
        if ([] === $variants) {
            throw new \DomainException('Aucune variante à programmer.');
        }
        if ($scheduledAt <= $this->nowUtc()) {
            throw new \DomainException('La date de publication doit être future.');
        }

        foreach ($variants as $variant) {
            if (ClotheStatus::Publishable !== $variant->getPublicationStatus()) {
                throw new \DomainException('Toutes les variantes doivent être publiables pour programmer le vêtement.');
            }
            if (!$this->clothePublicationStateMachine->can($variant, 'programmer_publication')) {
                throw new \DomainException(sprintf('La variante « %s » ne peut pas être programmée.', $variant->getName()));
            }
        }

        $now = $this->nowUtc();
        foreach ($variants as $variant) {
            $variant
                ->setScheduledPublicationAt($scheduledAt)
                ->setEditedAt($now);
            $this->clothePublicationStateMachine->apply($variant, 'programmer_publication');
        }

        $this->entityManager->flush();
    }

    public function reconcileCompleteness(Clothes $clothe): void
    {
        foreach ($clothe->getVariants() as $variant) {
            $this->reconcileVariant($variant);
        }
    }

    private function nowUtc(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone(self::STORAGE_TIMEZONE));
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

        if (null === $transition) {
            return false;
        }

        $this->apply($variant, $transition);

        return true;
    }

    public function reconcilePublicationEligibilityWithoutFlush(ClothesVariant $variant): bool
    {
        $complete = $this->completenessChecker->checkVariant($variant)->isComplete();
        $transitions = match ($variant->getPublicationStatus()) {
            ClotheStatus::Draft => $complete ? ['rendre_publiable'] : [],
            ClotheStatus::Publishable => $complete ? [] : ['repasser_en_brouillon'],
            ClotheStatus::Scheduled => $complete ? [] : ['invalider_programmation'],
            ClotheStatus::Online => $complete ? [] : ['depublier', 'modifier_hors_ligne'],
            default => [],
        };

        if ([] === $transitions) {
            return false;
        }

        foreach ($transitions as $transition) {
            if (!$this->clothePublicationStateMachine->can($variant, $transition)) {
                throw new \DomainException(sprintf(
                    'La transition automatique « %s » est impossible depuis l’état %s.',
                    $transition,
                    $variant->getPublicationStatus()->label(),
                ));
            }

            $this->applyWithoutFlush($variant, $transition);
        }

        return true;
    }
}
