<?php

namespace App\Tests\Clothes\Integration;

use App\Application\Clothes\Services\Color\ClotheColorDeletionService;
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

/** Vérifie ensemble Doctrine et le nettoyage des images lors de la suppression d’une couleur. */
#[Group('clothes')]
#[Group('integration')]
final class ClotheColorDeletionServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private string $token;
    private string $imageDirectory;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $database = (string) $this->entityManager->getConnection()->getDatabase();
        self::assertStringEndsWith('_test', $database, 'Sécurité : la suppression exige la base de test.');
        $this->token = bin2hex(random_bytes(2));
        $this->imageDirectory = self::getContainer()->getParameter('kernel.project_dir')
            . '/public/images/upload/clothes/delete-color-' . $this->token;
        mkdir($this->imageDirectory, 0775, true);
    }

    public function testDeletionRemovesLinkedVariantsAndImagesWithoutTouchingSurvivors(): void
    {
        $fixtures = $this->createFixtures();
        $firstImage = $this->imageDirectory . '/first.png';
        $secondImage = $this->imageDirectory . '/second.png';
        file_put_contents($firstImage, 'first-image');
        file_put_contents($secondImage, 'second-image');

        /** @var ClotheColorDeletionService $service */
        $service = self::getContainer()->get(ClotheColorDeletionService::class);
        $result = $service->delete($fixtures['deletedColor']);

        self::assertSame(2, $result['variants'], 'Blocage : tous les variants de la couleur ne sont pas supprimés.');
        self::assertSame(2, $result['images'], 'Blocage : les images physiques des variants ne sont pas supprimées.');
        self::assertFileDoesNotExist($firstImage, 'Blocage : la première image existe encore sur le disque.');
        self::assertFileDoesNotExist($secondImage, 'Blocage : la seconde image existe encore sur le disque.');

        $this->entityManager->clear();
        self::assertNull(
            $this->entityManager->find(Clothescolor::class, $fixtures['deletedColorId']),
            'Blocage : la couleur existe encore dans la base.',
        );
        self::assertNull(
            $this->entityManager->find(Clothes::class, $fixtures['emptyClotheId']),
            'Blocage : le vêtement devenu vide existe encore.',
        );
        self::assertInstanceOf(
            Clothes::class,
            $this->entityManager->find(Clothes::class, $fixtures['survivingClotheId']),
            'Blocage : un vêtement possédant une autre couleur a été supprimé.',
        );
        self::assertInstanceOf(
            ClothesVariant::class,
            $this->entityManager->find(ClothesVariant::class, $fixtures['survivingVariantId']),
            'Blocage : le variant d’une autre couleur a été supprimé.',
        );
    }

    /**
     * @return array{
     *     deletedColor: Clothescolor,
     *     deletedColorId: int,
     *     emptyClotheId: int,
     *     survivingClotheId: int,
     *     survivingVariantId: int
     * }
     */
    private function createFixtures(): array
    {
        $now = new \DateTimeImmutable();
        $category = (new Category())
            ->setName('Catégorie suppression ' . $this->token)
            ->setSlug('categorie-suppression-' . $this->token)
            ->setIsOnline(true)
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $collection = (new Collections())
            ->setName('Collection suppression ' . $this->token)
            ->setCategory($category)
            ->setIsOnline(true)
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $deletedColor = $this->color('Couleur supprimée ' . $this->token, 'aa1122', $now);
        $survivingColor = $this->color('Couleur conservée ' . $this->token, '11aa22', $now);
        $size = (new Clothessize())->setName('S' . $this->token)->setCreatedAt($now)->setEditedAt($now);
        $survivingClothe = $this->clothe('Vêtement conservé ' . $this->token, $collection, $now);
        $emptyClothe = $this->clothe('Vêtement vide ' . $this->token, $collection, $now);

        $deletedVariantOne = $this->variant(
            $survivingClothe,
            $deletedColor,
            $size,
            'supprime-1-' . $this->token,
            '/images/upload/clothes/delete-color-' . $this->token . '/first.png',
            $now,
        );
        $deletedVariantTwo = $this->variant(
            $emptyClothe,
            $deletedColor,
            $size,
            'supprime-2-' . $this->token,
            '/images/upload/clothes/delete-color-' . $this->token . '/second.png',
            $now,
        );
        $survivingVariant = $this->variant(
            $survivingClothe,
            $survivingColor,
            $size,
            'conserve-' . $this->token,
            '/images/external/conserve.png',
            $now,
        );

        $entities = [
            $category,
            $collection,
            $deletedColor,
            $survivingColor,
            $size,
            $survivingClothe,
            $emptyClothe,
        ];
        foreach ($entities as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();

        return [
            'deletedColor' => $deletedColor,
            'deletedColorId' => (int) $deletedColor->getId(),
            'emptyClotheId' => (int) $emptyClothe->getId(),
            'survivingClotheId' => (int) $survivingClothe->getId(),
            'survivingVariantId' => (int) $survivingVariant->getId(),
        ];
    }

    private function color(string $name, string $hexa, \DateTimeImmutable $now): Clothescolor
    {
        return (new Clothescolor())->setName($name)->setHexa($hexa)->setCreatedAt($now)->setEditedAt($now);
    }

    private function clothe(string $name, Collections $collection, \DateTimeImmutable $now): Clothes
    {
        return (new Clothes())
            ->setName($name)
            ->setPrice(5000)
            ->setCollection($collection)
            ->setCreatedAt($now)
            ->setEditedAt($now);
    }

    private function variant(
        Clothes $clothe,
        Clothescolor $color,
        Clothessize $size,
        string $name,
        string $image,
        \DateTimeImmutable $now,
    ): ClothesVariant {
        $variant = (new ClothesVariant())
            ->setName($name)
            ->setSlug($name)
            ->setColor($color)
            ->setSize($size)
            ->setSku(strtoupper($name))
            ->setImages([$image])
            ->setHighlightImage($image)
            ->setBestsellerImage($image)
            ->setPublicationStatus(ClotheStatus::Draft)
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $clothe->addVariant($variant);
        $color->addVariant($variant);

        return $variant;
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager, $this->token)) {
            $connection = $this->entityManager->getConnection();
            $parameters = ['token' => '%' . $this->token . '%'];
            $connection->executeStatement('DELETE FROM clothes_variant WHERE name LIKE :token', $parameters);
            $connection->executeStatement('DELETE FROM clothes WHERE name LIKE :token', $parameters);
            $connection->executeStatement('DELETE FROM clothescolor WHERE name LIKE :token', $parameters);
            $connection->executeStatement('DELETE FROM clothessize WHERE name LIKE :token', $parameters);
            $connection->executeStatement('DELETE FROM collections WHERE name LIKE :token', $parameters);
            $connection->executeStatement('DELETE FROM category WHERE name LIKE :token', $parameters);
        }
        if (isset($this->imageDirectory) && is_dir($this->imageDirectory)) {
            rmdir($this->imageDirectory);
        }

        parent::tearDown();
    }
}
