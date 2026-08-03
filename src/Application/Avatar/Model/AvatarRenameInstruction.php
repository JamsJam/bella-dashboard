<?php

namespace App\Application\Avatar\Model;

final readonly class AvatarRenameInstruction
{
    /** @param array<string, mixed> $filters */
    public function __construct(
        public int $avatarTempId,
        public string $newName,
        public string $category,
        public array $filters,
    ) {
    }
}
