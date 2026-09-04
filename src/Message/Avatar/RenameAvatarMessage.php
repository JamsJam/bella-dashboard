<?php

namespace App\Message\Avatar;

final readonly class RenameAvatarMessage
{
    public function __construct(
        public int $avatarTempId,
    ) {
    }
}
