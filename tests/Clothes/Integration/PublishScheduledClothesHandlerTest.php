<?php

namespace App\Tests\Clothes\Integration;

use App\Entity\Category\Category;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Collections\Collections;
use App\Enum\ClotheStatus;
use App\Scheduler\Task\PublishScheduledClothes\PublishScheduledClothesHandler;
use App\Scheduler\Task\PublishScheduledClothes\PublishScheduledClothesMessage;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('integration')]
#[Group('clothes')]
/** Vérifie le traitement réel des échéances par le scheduler de publication. */
final class PublishScheduledClothesHandlerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $databaseName = (string) $this->entityManager->getConnection()->getDatabase();
        if (!str_ends_with($databaseName, '_test')) {
            self::fail(sprintf('Refusing to run scheduler test against non-test database "%s".', $databaseName));
        }

        $this->entityManager->getConnection()->beginTransaction();
    }

    public function testHandlerPublishesDueCompleteVariantAndInvalidatesDueIncompleteVariant(): void
    {
        [$completeId, $incompleteId, $futureId] = $this->persistScheduledVariants();
        $handler = self::getContainer()->get(PublishScheduledClothesHandler::class);

        $handler(new PublishScheduledClothesMessage());
        $this->entityManager->clear();

        $complete = $this->entityManager->find(ClothesVariant::class, $completeId);
        $incomplete = $this->entityManager->find(ClothesVariant::class, $incompleteId);
        $future = $this->entityManager->find(ClothesVariant::class, $futureId);

        self::assertInstanceOf(ClothesVariant::class, $complete);
        self::assertSame(
            ClotheStatus::Online,
            $complete->getPublicationStatus(),
            'Blocage : le scheduler ne publie pas un variant complet arrivé à échéance.',
        );
        self::assertNotNull(
            $complete->getPublishedAt(),
            'Blocage : la publication automatique ne renseigne pas sa date effective.',
        );
        self::assertNull(
            $complete->getScheduledPublicationAt(),
            'Blocage : une publication effectuée conserve à tort sa date programmée.',
        );

        self::assertInstanceOf(ClothesVariant::class, $incomplete);
        self::assertSame(
            ClotheStatus::Draft,
            $incomplete->getPublicationStatus(),
            'Sécurité : le scheduler publie un variant incomplet arrivé à échéance.',
        );
        self::assertNull(
            $incomplete->getScheduledPublicationAt(),
            'Blocage : la programmation invalide conserve sa date.',
        );

        self::assertInstanceOf(ClothesVariant::class, $future);
        self::assertSame(
            ClotheStatus::Scheduled,
            $future->getPublicationStatus(),
            'Sécurité : le scheduler publie un variant avant son échéance.',
        );
        self::assertNotNull(
            $future->getScheduledPublicationAt(),
            'Blocage : le scheduler efface une programmation future.',
        );
    }

    /** @return array{int, int, int} */
    private function persistScheduledVariants(): array
    {
        $token = bin2hex(random_bytes(5));
        $now = new \DateTimeImmutable();
        $category = (new Category())
            ->setName('Scheduler category ' . $token)
            ->setSlug('scheduler-category-' . $token)
            ->setIsOnline(true)
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $collection = (new Collections())
            ->setName('Scheduler collection ' . $token)
            ->setCategory($category)
            ->setIsOnline(true)
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $clothe = (new Clothes())
            ->setName('Scheduler clothe ' . $token)
            ->setPrice(5900)
            ->setCollection($collection)
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $color = (new Clothescolor())
            ->setName('Scheduler color ' . $token)
            ->setHexa('112233')
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $small = $this->findOrCreateSize('S', $now);
        $medium = $this->findOrCreateSize('M', $now);
        $large = $this->findOrCreateSize('L', $now);

        $complete = $this->scheduledVariant($clothe, $color, $small, $token . '-s', new \DateTimeImmutable('-1 minute'));
        $incomplete = $this->scheduledVariant($clothe, $color, $medium, $token . '-m', new \DateTimeImmutable('-1 minute'))
            ->setMetadescription(null);
        $future = $this->scheduledVariant($clothe, $color, $large, $token . '-l', new \DateTimeImmutable('+1 hour'));
        $clothe->addVariant($complete)->addVariant($incomplete)->addVariant($future);

        foreach ([$category, $collection, $clothe, $color] as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();

        return [(int) $complete->getId(), (int) $incomplete->getId(), (int) $future->getId()];
    }

    private function findOrCreateSize(string $name, \DateTimeImmutable $now): Clothessize
    {
        $size = $this->entityManager->getRepository(Clothessize::class)->findOneBy(['name' => $name]);
        if ($size instanceof Clothessize) {
            return $size;
        }

        $size = (new Clothessize())->setName($name)->setCreatedAt($now)->setEditedAt($now);
        $this->entityManager->persist($size);

        return $size;
    }

    private function scheduledVariant(
        Clothes $clothe,
        Clothescolor $color,
        Clothessize $size,
        string $token,
        \DateTimeImmutable $scheduledAt,
    ): ClothesVariant {
        return (new ClothesVariant())
            ->setClothes($clothe)
            ->setColor($color)
            ->setSize($size)
            ->setName('Scheduled variant ' . $token)
            ->setSlug('scheduled-variant-' . $token)
            ->setSku('SCHEDULED-' . strtoupper($token))
            ->setMetadescription('Meta description ' . $token)
            ->setImages(['/images/' . $token . '.jpg'])
            ->setStock(0)
            ->setPublicationStatus(ClotheStatus::Scheduled)
            ->setScheduledPublicationAt($scheduledAt)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setEditedAt(new \DateTimeImmutable());
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
