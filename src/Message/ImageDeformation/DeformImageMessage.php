<?php

namespace App\Message\ImageDeformation;

final readonly class DeformImageMessage
{
    public function __construct(public string $jobId)
    {
    }
}
