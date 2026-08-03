<?php

namespace App\Tests\Application\Avatar\Workflow;

use App\Application\Avatar\Workflow\AvatarRenameValidationContext;
use App\Application\Avatar\Workflow\AvatarRenameGuardContextStore;
use App\Application\Avatar\Workflow\Guard\AvatarOverwriteAuthorizationGuard;
use App\Entity\AvatarTemp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;

final class AvatarOverwriteAuthorizationGuardTest extends TestCase
{
    #[DataProvider('cases')]
    public function testItOnlyBlocksAnUnconfirmedCollision(bool $exists, bool $authorization, bool $blocked): void
    {
        $context = new AvatarRenameValidationContext('eyes__blue__round.png', 'eyes', [], $authorization);
        $context->recordAvailability($exists);
        $avatarTemp = new AvatarTemp();
        $store = new AvatarRenameGuardContextStore();
        $store->setValidation($avatarTemp, $context);
        $event = new GuardEvent($avatarTemp, new Marking(['uploaded' => 1]), new Transition('validate', 'uploaded', 'validated'));

        (new AvatarOverwriteAuthorizationGuard($store))($event);

        self::assertSame($blocked, $event->isBlocked());
    }

    public static function cases(): iterable
    {
        yield 'available, not authorized' => [false, false, false];
        yield 'available, authorized' => [false, true, false];
        yield 'collision, not authorized' => [true, false, true];
        yield 'collision, authorized' => [true, true, false];
    }
}
