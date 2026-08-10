<?php

namespace App\Application\Avatar\Exception;

final class AvatarColorNotFoundException extends \RuntimeException
{
    public function __construct(string $type, int $id)
    {
        parent::__construct(sprintf('Avatar color "%s" with id %d was not found.', $type, $id));
    }
}
