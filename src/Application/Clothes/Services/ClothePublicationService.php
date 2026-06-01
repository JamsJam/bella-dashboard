<?php

namespace App\Application\Clothes\Services;

use App\Application\Clothes\Guard\ClotheOnlineGuard;
use App\Entity\Clothes\Clothes;
use App\Notifier\Services\FlashService;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ClothePublicationService
{
    public function __construct(
        private ClotheOnlineGuard $clotheOnlineGuard,
        private EntityManagerInterface $entityManager,
        private FlashService $flashService,
    ) {
    }

    public function publish(Clothes $clothe): bool
    {
        $result = $this->clotheOnlineGuard->canPublish($clothe);

        if (!$result->canPublish()) {
            $this->flashService->error('Le vêtement ne peut pas être mis en ligne : '.implode(', ', $result->getErrors()));

            return false;
        }

        $clothe->setIsOnline(true);
        $this->entityManager->flush();
        $this->flashService->success('Le vêtement est en ligne.');

        return true;
    }

    public function unpublish(Clothes $clothe): void
    {
        $clothe->setIsOnline(false);
        $this->entityManager->flush();
        $this->flashService->success('Le vêtement est hors ligne.');
    }
}
