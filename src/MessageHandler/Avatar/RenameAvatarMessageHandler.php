<?php

namespace App\MessageHandler\Avatar;

use App\Application\Avatar\Services\AvatarRenameService;
use App\Message\Avatar\RenameAvatarMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class RenameAvatarMessageHandler
{
    public function __construct(
        private AvatarRenameService $avatarRenameService,
    ) {
    }

    public function __invoke(RenameAvatarMessage $message): void
    {
        $this->avatarRenameService->renameFromMessage($message);
    }
}
