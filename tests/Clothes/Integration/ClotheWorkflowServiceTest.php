<?php

namespace App\Tests\Clothes\Integration;

use App\Application\Clothes\Services\ClotheWorkflowService;
use App\Entity\Category\Category;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Collections\Collections;
use App\Enum\ClotheStatus;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('integration')]
#[Group('clothes')]
/** Vérifie le service Workflow réel et la persistance MySQL de ses transitions. */
final class ClotheWorkflowServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ClotheWorkflowService $workflowService;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->workflowService = self::getContainer()->get(ClotheWorkflowService::class);

        $databaseName = (string) $this->entityManager->getConnection()->getDatabase();
        if (!str_ends_with($databaseName, '_test')) {
            self::fail(sprintf('Refusing to run clothes integration test against non-test database "%s".', $databaseName));
        }

        $this->entityManager->getConnection()->beginTransaction();
    }

    public function testRealServiceSchedulesAndPersistsSelectedPublishableVariants(): void
    {
        [$first, $second] = $this->persistVariantGroup(
            ClotheStatus::Publishable,
            ClotheStatus::Publishable,
        );
        $firstId = (int) $first->getId();
        $secondId = (int) $second->getId();
        $scheduledAt = new \DateTimeImmutable('+1 day');

        $this->workflowService->scheduleAll([$first, $second], $scheduledAt);
        $this->entityManager->clear();

        $reloadedFirst = $this->entityManager->find(ClothesVariant::class, $firstId);
        $reloadedSecond = $this->entityManager->find(ClothesVariant::class, $secondId);

        self::assertInstanceOf(ClothesVariant::class, $reloadedFirst);
        self::assertInstanceOf(ClothesVariant::class, $reloadedSecond);
        self::assertSame(ClotheStatus::Scheduled, $reloadedFirst->getPublicationStatus());
        self::assertSame(ClotheStatus::Scheduled, $reloadedSecond->getPublicationStatus());
        self::assertSame($scheduledAt->format('Y-m-d H:i:s'), $reloadedFirst->getScheduledPublicationAt()?->format('Y-m-d H:i:s'));
        self::assertSame($scheduledAt->format('Y-m-d H:i:s'), $reloadedSecond->getScheduledPublicationAt()?->format('Y-m-d H:i:s'));
    }

    public function testRealServiceOnlyProgramsTheSelectedPublishableVariant(): void
    {
        [$selected, $notSelected] = $this->persistVariantGroup(
            ClotheStatus::Publishable,
            ClotheStatus::Draft,
        );
        $selectedId = (int) $selected->getId();
        $notSelectedId = (int) $notSelected->getId();
        $scheduledAt = new \DateTimeImmutable('+1 day');

        $this->workflowService->scheduleAll([$selected], $scheduledAt);
        $this->entityManager->clear();

        $reloadedSelected = $this->entityManager->find(ClothesVariant::class, $selectedId);
        $reloadedNotSelected = $this->entityManager->find(ClothesVariant::class, $notSelectedId);

        self::assertInstanceOf(ClothesVariant::class, $reloadedSelected);
        self::assertInstanceOf(ClothesVariant::class, $reloadedNotSelected);
        self::assertSame(ClotheStatus::Scheduled, $reloadedSelected->getPublicationStatus());
        self::assertSame($scheduledAt->format('Y-m-d H:i:s'), $reloadedSelected->getScheduledPublicationAt()?->format('Y-m-d H:i:s'));
        self::assertSame(ClotheStatus::Draft, $reloadedNotSelected->getPublicationStatus());
        self::assertNull($reloadedNotSelected->getScheduledPublicationAt());
    }

    public function testRealServiceRejectsTheSelectionBeforePersistingAnyTransition(): void
    {
        [$publishable, $draft] = $this->persistVariantGroup(
            ClotheStatus::Publishable,
            ClotheStatus::Draft,
        );
        $publishableId = (int) $publishable->getId();
        $draftId = (int) $draft->getId();

        try {
            $this->workflowService->scheduleAll([$publishable, $draft], new \DateTimeImmutable('+1 day'));
            self::fail('The mixed-status selection should have been rejected.');
        } catch (\DomainException $exception) {
            self::assertStringContainsString('Toutes les variantes doivent être publiables', $exception->getMessage());
        }

        $this->entityManager->clear();
        $reloadedPublishable = $this->entityManager->find(ClothesVariant::class, $publishableId);
        $reloadedDraft = $this->entityManager->find(ClothesVariant::class, $draftId);

        self::assertInstanceOf(ClothesVariant::class, $reloadedPublishable);
        self::assertInstanceOf(ClothesVariant::class, $reloadedDraft);
        self::assertSame(ClotheStatus::Publishable, $reloadedPublishable->getPublicationStatus());
        self::assertSame(ClotheStatus::Draft, $reloadedDraft->getPublicationStatus());
        self::assertNull($reloadedPublishable->getScheduledPublicationAt());
        self::assertNull($reloadedDraft->getScheduledPublicationAt());
    }

    /**
     * @return array{ClothesVariant, ClothesVariant}
     */
    private function persistVariantGroup(ClotheStatus $firstStatus, ClotheStatus $secondStatus): array
    {
        $token = 'it_' . bin2hex(random_bytes(5));
        $now = new \DateTimeImmutable();

        $category = (new Category())
            ->setName('Category ' . $token)
            ->setSlug('category-' . $token)
            ->setIsOnline(true)
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $collection = (new Collections())
            ->setName('Collection ' . $token)
            ->setCategory($category)
            ->setIsOnline(true)
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $clothe = (new Clothes())
            ->setName('Clothe ' . $token)
            ->setPrice(1000)
            ->setCollection($collection)
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $color = (new Clothescolor())
            ->setName('Color ' . $token)
            ->setHexa('112233')
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $small = (new Clothessize())
            ->setName('S')
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $medium = (new Clothessize())
            ->setName('M')
            ->setCreatedAt($now)
            ->setEditedAt($now);

        $first = $this->variant($clothe, $color, $small, $token . '-s', $firstStatus);
        $second = $this->variant($clothe, $color, $medium, $token . '-m', $secondStatus);
        $clothe->addVariant($first)->addVariant($second);

        foreach ([$category, $collection, $clothe, $color, $small, $medium] as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();

        return [$first, $second];
    }

    private function variant(
        Clothes $clothe,
        Clothescolor $color,
        Clothessize $size,
        string $token,
        ClotheStatus $status,
    ): ClothesVariant {
        return (new ClothesVariant())
            ->setClothes($clothe)
            ->setColor($color)
            ->setSize($size)
            ->setName('Variant ' . $token)
            ->setSlug('clothe-' . $token)
            ->setSku('SKU-' . $token)
            ->setImages(['/images/' . $token . '.jpg'])
            ->setStock(0)
            ->setPublicationStatus($status);
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $connection = $this->entityManager->getConnection();
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $this->entityManager->clear();
        }

        parent::tearDown();
    }
}
