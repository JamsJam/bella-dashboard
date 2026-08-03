<?php

namespace App\Application\Avatar\Workflow;

final class AvatarRenameValidationContext
{
    private ?bool $targetAlreadyExists = null;
    private ?string $previewUrl = null;

    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(
        public readonly string $newName,
        public readonly string $category,
        public readonly array $filters,
        public readonly bool $authorization,
    ) {
    }

    public function recordAvailability(bool $targetAlreadyExists, ?string $previewUrl = null): void
    {
        $this->targetAlreadyExists = $targetAlreadyExists;
        $this->previewUrl = $previewUrl;
    }

    public function wasChecked(): bool
    {
        return $this->targetAlreadyExists !== null;
    }

    public function targetAlreadyExists(): bool
    {
        return $this->targetAlreadyExists === true;
    }

    public function previewUrl(): ?string
    {
        return $this->previewUrl;
    }
}
