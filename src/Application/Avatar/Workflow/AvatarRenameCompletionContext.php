<?php

namespace App\Application\Avatar\Workflow;

final readonly class AvatarRenameCompletionContext
{
    public function __construct(
        public string $destinationPath,
        public string $expectedName,
        public string $expectedChecksum,
        public string $imageWebPath,
        public object $avatarPart,
    ) {
    }
}
