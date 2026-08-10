<?php

namespace App\Application\Avatar\Exception;

final class AvatarPartNotFoundException extends \RuntimeException
{
    public function __construct(string $part, int $id)
    {
        parent::__construct(sprintf('Avatar part "%s" with id %d was not found.', $part, $id));
    }
}
