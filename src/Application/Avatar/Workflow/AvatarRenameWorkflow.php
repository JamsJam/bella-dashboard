<?php

namespace App\Application\Avatar\Workflow;

final class AvatarRenameWorkflow
{
    public const NAME = 'avatar_rename';

    public const PLACE_UPLOADED = 'uploaded';
    public const PLACE_VALIDATED = 'validated';
    public const PLACE_RENAMING = 'renaming';
    public const PLACE_RENAMED = 'renamed';
    public const PLACE_ERROR = 'error';

    public const TRANSITION_VALIDATE = 'validate';
    public const TRANSITION_START_RENAMING = 'start_renaming';
    public const TRANSITION_CANCEL_VALIDATION = 'cancel_validation';
    public const TRANSITION_MARK_RENAMED = 'mark_renamed';
    public const TRANSITION_FAIL = 'fail';
    public const TRANSITION_RETRY = 'retry';

    private function __construct()
    {
    }
}
