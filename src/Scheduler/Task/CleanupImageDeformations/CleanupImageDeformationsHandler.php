<?php

namespace App\Scheduler\Task\CleanupImageDeformations;

use App\Application\ImageDeformation\ImageDeformationJobStorage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CleanupImageDeformationsHandler
{
    public function __construct(
        private ImageDeformationJobStorage $storage,
    ) {
    }

    public function __invoke(CleanupImageDeformationsMessage $message): void
    {
        $this->storage->cleanupAll();
    }
}
