<?php

namespace App\Tests\Avatar\Unit\Workflow;

use App\Application\Avatar\Workflow\AvatarRenameWorkflow as Names;
use App\Entity\AvatarTemp;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;

/** Vérifie toutes les transitions métier du cycle de renommage Avatar. */
#[Group('avatar')]
#[Group('unit')]
final class AvatarRenameWorkflowTest extends TestCase
{
    public function testAllExpectedTransitionsAndRetryPath(): void
    {
        $workflow = $this->workflow();
        $avatar = new AvatarTemp();

        self::assertSame(Names::PLACE_UPLOADED, $avatar->getStatus());

        $workflow->apply($avatar, Names::TRANSITION_VALIDATE);
        self::assertSame(Names::PLACE_VALIDATED, $avatar->getStatus());

        $workflow->apply($avatar, Names::TRANSITION_CANCEL_VALIDATION);
        self::assertSame(Names::PLACE_UPLOADED, $avatar->getStatus());

        $workflow->apply($avatar, Names::TRANSITION_VALIDATE);

        $workflow->apply($avatar, Names::TRANSITION_START_RENAMING);
        self::assertSame(Names::PLACE_RENAMING, $avatar->getStatus());

        $workflow->apply($avatar, Names::TRANSITION_FAIL);
        self::assertSame(Names::PLACE_ERROR, $avatar->getStatus());

        $workflow->apply($avatar, Names::TRANSITION_RETRY);
        self::assertSame(Names::PLACE_UPLOADED, $avatar->getStatus());

        $workflow->apply($avatar, Names::TRANSITION_VALIDATE);
        $workflow->apply($avatar, Names::TRANSITION_START_RENAMING);
        $workflow->apply($avatar, Names::TRANSITION_MARK_RENAMED);
        self::assertSame(Names::PLACE_RENAMED, $avatar->getStatus());
        self::assertFalse($workflow->can($avatar, Names::TRANSITION_START_RENAMING));
    }

    private function workflow(): Workflow
    {
        return new Workflow(
            new Definition(
                [Names::PLACE_UPLOADED, Names::PLACE_VALIDATED, Names::PLACE_RENAMING, Names::PLACE_RENAMED, Names::PLACE_ERROR],
                [
                    new Transition(Names::TRANSITION_VALIDATE, Names::PLACE_UPLOADED, Names::PLACE_VALIDATED),
                    new Transition(Names::TRANSITION_START_RENAMING, Names::PLACE_VALIDATED, Names::PLACE_RENAMING),
                    new Transition(Names::TRANSITION_CANCEL_VALIDATION, Names::PLACE_VALIDATED, Names::PLACE_UPLOADED),
                    new Transition(Names::TRANSITION_MARK_RENAMED, Names::PLACE_RENAMING, Names::PLACE_RENAMED),
                    new Transition(Names::TRANSITION_FAIL, Names::PLACE_RENAMING, Names::PLACE_ERROR),
                    new Transition(Names::TRANSITION_RETRY, Names::PLACE_ERROR, Names::PLACE_UPLOADED),
                ],
                Names::PLACE_UPLOADED,
            ),
            new MethodMarkingStore(true, 'status'),
            name: Names::NAME,
        );
    }
}
