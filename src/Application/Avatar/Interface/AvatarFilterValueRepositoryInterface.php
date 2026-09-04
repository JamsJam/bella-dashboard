<?php

namespace App\Application\Avatar\Interface;

interface AvatarFilterValueRepositoryInterface
{
    public function findOrCreate(string $name): object;
}
