<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\Avatar\AvatarFilterFixtures;
use App\DataFixtures\Avatar\BodyFixtures;
use App\DataFixtures\Avatar\EyebrowsFixtures;
use App\DataFixtures\Avatar\EyesFixtures;
use App\DataFixtures\Avatar\FaceFixtures;
use App\DataFixtures\Avatar\HairFixtures;
use App\DataFixtures\Avatar\MouthFixtures;
use App\DataFixtures\Avatar\NoseFixtures;
use App\DataFixtures\Clothes\ClothesFixtures;
use App\Entity\Avatar\Body\Body;
use App\Entity\Avatar\Body\Bodysize;
use App\Entity\Avatar\Body\Morphologie;
use App\Entity\Avatar\Body\Morphotype;
use App\Entity\Avatar\Eyebrows\Eyebrows;
use App\Entity\Avatar\Eyes\Eye;
use App\Entity\Avatar\Faces\Faces;
use App\Entity\Avatar\Hairs\Hairs;
use App\Entity\Avatar\Mouths\Mouths;
use App\Entity\Avatar\Noses\Nose;
use App\Entity\Avatar\Skincolor;
use App\Entity\Category\Category;
use App\Entity\Clothes\Clothes;
use App\Entity\Collections\Collections;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('fixtures')]
final class FixtureDatasetTest extends TestCase
{
    public function testCommerceAndAvatarDatasetHasTheExpectedCombinations(): void
    {
        $persisted = [];
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('persist')->willReturnCallback(static function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });
        $manager->method('getClassMetadata')->willReturnCallback(static fn (string $className): ClassMetadata => new ClassMetadata($className));
        $unitOfWork = $this->createStub(UnitOfWork::class);
        $unitOfWork->method('isInIdentityMap')->willReturn(false);
        $manager->method('getUnitOfWork')->willReturn($unitOfWork);

        $references = new ReferenceRepository($manager);
        $fixtures = [
            new ClothesFixtures(),
            new AvatarFilterFixtures(),
            new FaceFixtures(),
            new NoseFixtures(),
            new MouthFixtures(),
            new EyesFixtures(),
            new HairFixtures(),
            new EyebrowsFixtures(),
            new BodyFixtures(),
        ];

        foreach ($fixtures as $fixture) {
            $fixture->setReferenceRepository($references);
            $fixture->load($manager);
        }

        self::assertCount(1, $this->ofType($persisted, Category::class));
        self::assertSame('Chemises', $this->ofType($persisted, Category::class)[0]->getName());
        self::assertCount(1, $this->ofType($persisted, Collections::class));
        self::assertSame('Été', $this->ofType($persisted, Collections::class)[0]->getName());

        $clothes = $this->ofType($persisted, Clothes::class);
        self::assertCount(5, $clothes);
        foreach ($clothes as $index => $clothe) {
            $variants = $clothe->getVariants()->toArray();
            self::assertCount(ClothesFixtures::COLOR_COUNTS_BY_CLOTHE[$index] * 6, $variants);
            self::assertSame(
                ClothesFixtures::SIZES,
                array_values(array_unique(array_map(static fn ($variant): string => (string) $variant->getSize()?->getName(), $variants))),
            );
        }

        self::assertCount(3, $this->ofType($persisted, Skincolor::class));
        self::assertCount(5, $this->ofType($persisted, Morphologie::class));
        self::assertCount(4, $this->ofType($persisted, Bodysize::class));
        self::assertCount(20, $this->ofType($persisted, Morphotype::class));
        self::assertCount(18, $this->ofType($persisted, Faces::class));
        self::assertCount(12, $this->ofType($persisted, Nose::class));
        self::assertCount(4, $this->ofType($persisted, Mouths::class));
        self::assertCount(4, $this->ofType($persisted, Eye::class));
        self::assertCount(4, $this->ofType($persisted, Hairs::class));
        self::assertCount(4, $this->ofType($persisted, Eyebrows::class));
        $bodies = $this->ofType($persisted, Body::class);
        self::assertCount(1260, $bodies);

        foreach ($bodies as $body) {
            $variants = $body->getClothesVariants()->toArray();
            if (str_ends_with((string) $body->getName(), '__-none-')) {
                self::assertCount(0, $variants);
                continue;
            }

            self::assertCount(6, $variants);
            self::assertCount(1, array_unique(array_map(
                static fn ($variant): string => (string) $variant->getSlug(),
                $variants,
            )));
        }
    }

    /**
     * @template T of object
     *
     * @param list<object>    $entities
     * @param class-string<T> $className
     *
     * @return list<T>
     */
    private function ofType(array $entities, string $className): array
    {
        return array_values(array_filter(
            $entities,
            static fn (object $entity): bool => $entity instanceof $className,
        ));
    }
}
