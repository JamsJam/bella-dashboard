<?php

namespace App\Application\Avatar\Model;

use Symfony\Component\HttpFoundation\Response;

final readonly class AvatarUploadResult
{
    public function __construct(
        public bool $success,
        public string $status,
        public ?string $error = null,
        public ?string $file = null,
        public ?string $path = null,
        public int $httpStatus = Response::HTTP_OK,
        public array $extra = [],
    ) {
    }

    public static function error(string $message, int $httpStatus): self
    {
        return new self(
            success: false,
            status: 'error',
            error: $message,
            httpStatus: $httpStatus,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'success' => $this->success,
            'status' => $this->status,
            'error' => $this->error,
            'file' => $this->file,
            'path' => $this->path,
        ] + $this->extra, static fn (mixed $value): bool => $value !== null);
    }
}
