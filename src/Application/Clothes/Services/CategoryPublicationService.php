<?php

namespace App\Application\Clothes\Services;

use App\Application\Clothes\Guard\ClotheOnlineGuard;
use App\Application\Clothes\Guard\Category\CategoryOnlineGuard;
use App\Application\Clothes\Guard\Collection\CollectionOnlineGuard;
use App\Entity\Category\Category;
use App\Entity\Clothes\Clothes;
use App\Entity\Collections\Collections;
use App\Notifier\Services\FlashService;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CategoryPublicationService
{
    public function __construct(
        private CategoryOnlineGuard $categoryOnlineGuard,
        private CollectionOnlineGuard $collectionOnlineGuard,
        private ClotheOnlineGuard $clotheOnlineGuard,
        private EntityManagerInterface $entityManager,
        private FlashService $flashService,
    ) {
    }

    public function publish(Category $category): bool
    {
        $result = $this->categoryOnlineGuard->canPublish($category);

        if (!$result->canPublish()) {
            $this->flashService->error('La catégorie ne peut pas être mise en ligne : '.implode(', ', $result->getErrors()));

            return false;
        }

        $category->setIsOnline(true);
        $publishedCollections = 0;
        $publishedClothes = 0;

        foreach ($category->getCollections() as $collection) {
            if (!$collection instanceof Collections) {
                continue;
            }

            $collectionResult = $this->collectionOnlineGuard->canPublish($collection);
            if ($collectionResult->canPublish()) {
                $collection->setIsOnline(true);
                ++$publishedCollections;
            } else {
                $this->flashService->error(sprintf(
                    'La collection "%s" ne peut pas être mise en ligne : %s',
                    (string) $collection->getName(),
                    implode(', ', $collectionResult->getErrors()),
                ));
            }

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
        }

        $this->entityManager->flush();
        $this->flashService->success(sprintf(
            'La catégorie est en ligne. %d collection(s) et %d vêtement(s) mis en ligne.',
            $publishedCollections,
            $publishedClothes,
        ));

        return true;
    }

    public function unpublish(Category $category): void
    {
        $category->setIsOnline(false);
        $unpublishedCollections = 0;
        $unpublishedClothes = 0;

        foreach ($category->getCollections() as $collection) {
            if (!$collection instanceof Collections) {
                continue;
            }

            $collection->setIsOnline(false);
            ++$unpublishedCollections;

            foreach ($collection->getClothes() as $clothe) {
                if (!$clothe instanceof Clothes) {
                    continue;
                }

                $clothe->setIsOnline(false);
                ++$unpublishedClothes;
            }
        }

        $this->entityManager->flush();
        $this->flashService->success(sprintf(
            'La catégorie est hors ligne. %d collection(s) et %d vêtement(s) mis hors ligne.',
            $unpublishedCollections,
            $unpublishedClothes,
        ));
    }
}
