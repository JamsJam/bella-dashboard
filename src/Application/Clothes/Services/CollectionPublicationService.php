<?php

namespace App\Application\Clothes\Services;

use App\Application\Clothes\Guard\ClotheOnlineGuard;
use App\Application\Clothes\Guard\Collection\CollectionOnlineGuard;
use App\Entity\Clothes\Clothes;
use App\Entity\Collections\Collections;
use App\Notifier\Services\FlashService;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CollectionPublicationService
{
    public function __construct(
        private CollectionOnlineGuard $collectionOnlineGuard,
        private ClotheOnlineGuard $clotheOnlineGuard,
        private EntityManagerInterface $entityManager,
        private FlashService $flashService,
    ) {
    }

    public function publish(Collections $collection): bool
    {
        $result = $this->collectionOnlineGuard->canPublish($collection);

        if (!$result->canPublish()) {
            $this->flashService->error('La collection ne peut pas être mise en ligne : '.implode(', ', $result->getErrors()));

            return false;
        }

        $collection->setIsOnline(true);
        $publishedClothes = 0;

        foreach ($collection->getClothes() as $clothe) {
            if (!$clothe instanceof Clothes) {
                continue;
            }

            $clotheResult = $this->clotheOnlineGuard->canPublish($clothe);
            if ($clotheResult->canPublish()) {
                $clothe->setIsOnline(true);
                ++$publishedClothes;
            } else {
                $this->flashService->error(sprintf(
                    'Le vêtement "%s" ne peut pas être mis en ligne : %s',
                    (string) $clothe->getName(),
                    implode(', ', $clotheResult->getErrors()),
                ));
            }
        }

        $this->entityManager->flush();
        $this->flashService->success(sprintf(
            'La collection est en ligne. %d vêtement(s) mis en ligne.',
            $publishedClothes,
        ));

        return true;
    }

    public function unpublish(Collections $collection): void
    {
        $collection->setIsOnline(false);
        $unpublishedClothes = 0;

        foreach ($collection->getClothes() as $clothe) {
            if (!$clothe instanceof Clothes) {
                continue;
            }

            $clothe->setIsOnline(false);
            ++$unpublishedClothes;
        }

        $this->entityManager->flush();
        $this->flashService->success(sprintf(
            'La collection est hors ligne. %d vêtement(s) mis hors ligne.',
            $unpublishedClothes,
        ));
    }
}
