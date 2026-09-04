<?php

namespace App\Application\Clothes\Services\Clothe;

use App\Entity\Clothes\Clothes;
use App\Enum\ClotheStatus;
use App\Service\LoggerService;

final readonly class ClotheArchiveService
{
    public function __construct(
        private ClotheWorkflowService $workflowService,
        private LoggerService $logger,
    ) {
    }

    public function archive(Clothes $clothe): void
    {
        foreach ($clothe->getVariants() as $variant) {
            if (ClotheStatus::Archived === $variant->getPublicationStatus()) {
                continue;
            }

            $transition = match ($variant->getPublicationStatus()) {
                ClotheStatus::Draft => 'archiver_brouillon',
                ClotheStatus::Publishable => 'archiver_publiable',
                ClotheStatus::Scheduled => 'archiver_planifie',
                ClotheStatus::Online => 'archiver_en_ligne',
                ClotheStatus::Offline => 'archiver_hors_ligne',
                ClotheStatus::Archived => throw new \LogicException(
                    'État déjà archivé.',
                ),
            };

            $this->workflowService->apply($variant, $transition);
        }

        $this->logger->info('Clothe archived.', [
            'clothe_id' => $clothe->getId(),
            'slug' => $clothe->getSlug(),
        ]);
    }
}
