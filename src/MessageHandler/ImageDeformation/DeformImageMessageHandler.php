<?php

namespace App\MessageHandler\ImageDeformation;

use App\Application\ImageDeformation\ImageDeformationJobStorage;
use App\Application\ImageDeformation\ImageDeformationProcessor;
use App\Message\ImageDeformation\DeformImageMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DeformImageMessageHandler
{
    public function __construct(
        private ImageDeformationProcessor $processor,
        private ImageDeformationJobStorage $storage,
    ) {
    }

    public function __invoke(DeformImageMessage $message): void
    {
        $status = $this->storage->readStatus($message->jobId);
        if (null === $status || 'completed' === $status['status']) {
            return;
        }

        $this->storage->writeStatus($message->jobId, 'processing');

        try {
            $sourcePath = $this->storage->sourcePath($message->jobId);
            $resultPath = $this->storage->resultPath($message->jobId);
            $this->processor->process($sourcePath, $resultPath);
            $this->storage->writeStatus($message->jobId, 'completed');
        } catch (\Throwable $exception) {
            $this->storage->writeStatus($message->jobId, 'failed', $exception->getMessage());
            throw $exception;
        }
    }
}
