<?php

namespace App\Message\Avatar;

final readonly class RenameAvatarMessage
{
    public function __construct(
        public int $avatarTempId,
        public string $newName,
        public string $category,
        public array $filters,
        public bool $replaceExisting = false,
    ) {
    }
}
