<?php

namespace App\Scheduler\Task\PublishScheduledClothes;

use App\Application\Clothes\Services\ClotheCompletenessChecker;
use App\Application\Clothes\Services\ClotheWorkflowService;
use App\Repository\Clothes\ClothesVariantRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class PublishScheduledClothesHandler
{
    public function __construct(
        private ClothesVariantRepository $repository,
        private ClotheCompletenessChecker $completenessChecker,
        private ClotheWorkflowService $workflow,
    ) {
    }

    public function __invoke(PublishScheduledClothesMessage $message): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        foreach ($this->repository->findScheduledForPublication($now) as $variant) {
            $this->workflow->apply(
                $variant,
                $this->completenessChecker->checkVariant($variant)->isComplete()
                    ? 'publier_automatiquement'
                    : 'invalider_programmation',
            );
        }
    }
}
