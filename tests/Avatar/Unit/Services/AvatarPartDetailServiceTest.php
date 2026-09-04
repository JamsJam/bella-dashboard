<?php

namespace App\Tests\Avatar\Unit\Services;

use App\Application\Avatar\Dto\AvatarPartDetailDto;
use App\Application\Avatar\Exception\AvatarPartNotFoundException;
use App\Application\Avatar\Mapper\AvatarPartViewMapper;
use App\Application\Avatar\Provider\AvatarPartDetailProvider;
use App\Application\Avatar\Resolver\AvatarEntityResolver;
use App\Application\Avatar\Resolver\AvatarRepositoryResolver;
use App\Application\Avatar\Services\AvatarPartDetailService;
use App\Application\Avatar\Services\AvatarResolverService;
use App\Entity\Avatar\Eyes\Eye;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('avatar')]
#[Group('unit')]
final class AvatarPartDetailServiceTest extends TestCase
{
    public function testItBuildsTheDetailDto(): void
    {
        $shape = new class {
            public function getId(): int
            {
                return 4;
            }

            public function getName(): string
            {
                return 'Rond';
            }
        };
        $avatarPart = $this->avatarPart(10, 'eyes-10', $shape);
        $similarPart = $this->avatarPart(11, 'eyes-11', $shape);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('findAll')->willReturn([$avatarPart, $similarPart]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('find')->with(Eye::class, 10)->willReturn($avatarPart);
        $entityManager->expects(self::once())->method('getRepository')->with(Eye::class)->willReturn($repository);

        $detail = $this->service($entityManager)->getDetail('eyes', 10);

        self::assertInstanceOf(AvatarPartDetailDto::class, $detail);
        self::assertSame('eyes', $detail->part);
        self::assertSame(10, $detail->avatar->id);
        self::assertSame([11], array_map(static fn ($avatar): ?int => $avatar->id, $detail->similarAvatars));
        self::assertSame([], $detail->accessoryFaces);
        self::assertFalse($detail->showAccessoryFacesSection);
    }

    public function testItRejectsAnUnknownAvatarPartId(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('find')->with(Eye::class, 404)->willReturn(null);

        $this->expectException(AvatarPartNotFoundException::class);
        $this->service($entityManager)->getDetail('eyes', 404);
    }

    private function service(EntityManagerInterface $entityManager): AvatarPartDetailService
    {
        return new AvatarPartDetailService(
            new AvatarResolverService(new AvatarEntityResolver(), new AvatarRepositoryResolver()),
            new AvatarPartDetailProvider($entityManager),
            new AvatarPartViewMapper(),
        );
    }

    private function avatarPart(int $id, string $name, object $shape): object
    {
        return new class ($id, $name, $shape) {
            public function __construct(
                private readonly int $id,
                private readonly string $name,
                private readonly object $shape,
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

            public function getImage(): string
            {
                return sprintf('/eyes/%d.png', $this->id);
            }

            public function getShape(): object
            {
                return $this->shape;
            }
        };
    }
}
