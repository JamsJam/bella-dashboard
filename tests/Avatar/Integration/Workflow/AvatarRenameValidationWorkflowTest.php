<?php

namespace App\Tests\Avatar\Integration\Workflow;

use App\Application\Avatar\Workflow\AvatarRenameGuardContextStore;
use App\Application\Avatar\Workflow\AvatarRenameValidationContext;
use App\Application\Avatar\Workflow\AvatarRenameWorkflow;
use App\Entity\AvatarTemp;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

/** Vérifie l’assemblage réel du workflow Symfony et de ses gardes. */
#[Group('avatar')]
#[Group('integration')]
final class AvatarRenameValidationWorkflowTest extends KernelTestCase
{
    public function testValidationContextIsAvailableToBothGuards(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $workflow = $container->get('state_machine.avatar_rename');
        $store = $container->get(AvatarRenameGuardContextStore::class);

        self::assertInstanceOf(WorkflowInterface::class, $workflow);
        self::assertInstanceOf(AvatarRenameGuardContextStore::class, $store);

        $avatarTemp = new AvatarTemp();
        $context = new AvatarRenameValidationContext(
            'nose__noir__rond.png',
            'nose',
            ['skinColor' => ['name' => 'noir', 'hexa' => '#000000'], 'shape' => 'rond'],
            false,
        );
        $store->setValidation($avatarTemp, $context);

        try {
            $workflow->apply($avatarTemp, AvatarRenameWorkflow::TRANSITION_VALIDATE);
        } finally {
            $store->clearValidation($avatarTemp);
        }

        self::assertTrue($context->wasChecked(), 'Blocage : les gardes n’ont pas contrôlé le contexte de validation.');
        self::assertSame(
            AvatarRenameWorkflow::PLACE_VALIDATED,
            $avatarTemp->getStatus(),
            'Blocage : le workflow configuré n’a pas placé l’avatar dans l’état validé.',
        );
    }
}
