<?php

namespace App\Tests\Avatar\Unit\Services;

use App\Application\Avatar\Dto\AvatarColorModalDto;
use App\Application\Avatar\Exception\AvatarColorNotFoundException;
use App\Application\Avatar\Mapper\AvatarColorMapper;
use App\Application\Avatar\Provider\AvatarColorProvider;
use App\Application\Avatar\Resolver\AvatarColorTypeResolver;
use App\Application\Avatar\Services\AvatarColorService;
use App\Entity\Avatar\Skincolor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('avatar')]
#[Group('unit')]
final class AvatarColorServiceTest extends TestCase
{
    public function testItBuildsTheModalDto(): void
    {
        $associatedElement = new \stdClass();
        $color = $this->color(4, 'Clair', 'F1D0B5', [$associatedElement]);
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('findBy')->with([], ['name' => 'ASC'])->willReturn([$color]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('getRepository')->with(Skincolor::class)->willReturn($repository);

        $modal = $this->service($entityManager)->getModal('skin');

        self::assertInstanceOf(AvatarColorModalDto::class, $modal);
        self::assertSame('Peau', $modal->activeLabel);
        self::assertSame(4, $modal->colors[0]->id);
        self::assertSame(1, $modal->colors[0]->associatedCount);
        self::assertTrue($modal->tabs[0]->active);
    }

    public function testItDeletesTheColorAndItsAssociatedElements(): void
    {
        $associatedElement = new \stdClass();
        $color = $this->color(4, 'Clair', 'F1D0B5', [$associatedElement]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('find')->with(Skincolor::class, 4)->willReturn($color);
        $entityManager->expects(self::exactly(2))->method('remove')->willReturnCallback(
            static function (object $removed) use ($associatedElement, $color): void {
                static $position = 0;
                self::assertSame(0 === $position++ ? $associatedElement : $color, $removed);
            },
        );
        $entityManager->expects(self::once())->method('flush');

        self::assertSame(1, $this->service($entityManager)->delete('skin', 4));
    }

    public function testItRejectsAnUnknownColorId(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('find')->with(Skincolor::class, 404)->willReturn(null);

        $this->expectException(AvatarColorNotFoundException::class);
        $this->service($entityManager)->delete('skin', 404);
    }

    private function service(EntityManagerInterface $entityManager): AvatarColorService
    {
        return new AvatarColorService(
            new AvatarColorTypeResolver(),
            new AvatarColorProvider($entityManager),
            new AvatarColorMapper(),
        );
    }

    /** @param list<object> $noses */
    private function color(int $id, string $name, string $hexa, array $noses): object
    {
        return new class ($id, $name, $hexa, $noses) {
            public function __construct(
                private readonly int $id,
                private readonly string $name,
                private readonly string $hexa,
                private readonly array $noses,
            ) {
            }

            public function getId(): int
            {
                return $this->id;
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getHexa(): string
            {
                return $this->hexa;
            }

            public function getNoses(): array
            {
                return $this->noses;
            }

            public function getBodies(): array
            {
                return [];
            }

            public function getFaces(): array
            {
                return [];
            }
        };
    }
}
