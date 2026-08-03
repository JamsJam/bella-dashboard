<?php

namespace App\Application\Avatar\Workflow;

use App\Entity\AvatarTemp;

final class AvatarRenameGuardContextStore
{
    /** @var \WeakMap<AvatarTemp, AvatarRenameValidationContext> */
    private \WeakMap $validations;

    /** @var \WeakMap<AvatarTemp, AvatarRenameCompletionContext> */
    private \WeakMap $completions;

    public function __construct()
    {
        $this->validations = new \WeakMap();
        $this->completions = new \WeakMap();
    }

    public function setValidation(AvatarTemp $subject, AvatarRenameValidationContext $context): void
    {
        $this->validations[$subject] = $context;
    }

    public function validation(AvatarTemp $subject): ?AvatarRenameValidationContext
    {
        return $this->validations[$subject] ?? null;
    }

    public function clearValidation(AvatarTemp $subject): void
    {
        unset($this->validations[$subject]);
    }

    public function setCompletion(AvatarTemp $subject, AvatarRenameCompletionContext $context): void
    {
        $this->completions[$subject] = $context;
    }

    public function completion(AvatarTemp $subject): ?AvatarRenameCompletionContext
    {
        return $this->completions[$subject] ?? null;
    }

    public function clearCompletion(AvatarTemp $subject): void
    {
        unset($this->completions[$subject]);
    }
}
