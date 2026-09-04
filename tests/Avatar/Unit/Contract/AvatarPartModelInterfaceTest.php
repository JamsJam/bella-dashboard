<?php

namespace App\Tests\Avatar\Unit\Contract;

use App\Application\Avatar\Interface\AvatarPartModelInterface;
use App\Repository\Avatar\Body\BodyRepository;
use App\Repository\Avatar\Eyebrows\EyebrowsRepository;
use App\Repository\Avatar\Eyes\EyeRepository;
use App\Repository\Avatar\Faces\FacesRepository;
use App\Repository\Avatar\Hairs\HairsRepository;
use App\Repository\Avatar\Mouths\MouthsRepository;
use App\Repository\Avatar\Noses\NoseRepository;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/** Vérifie le contrat commun utilisé par les dépôts des différentes parties d’avatar. */
#[Group('avatar')]
#[Group('unit')]
final class AvatarPartModelInterfaceTest extends TestCase
{
    public function testAllAvatarPartRepositoriesImplementInterface(): void
    {
        $repositories = [
            BodyRepository::class,
            EyebrowsRepository::class,
            EyeRepository::class,
            FacesRepository::class,
            HairsRepository::class,
            MouthsRepository::class,
            NoseRepository::class,
        ];

        foreach ($repositories as $repositoryClass) {
            self::assertTrue(
                is_subclass_of($repositoryClass, AvatarPartModelInterface::class),
                sprintf('Blocage : le dépôt %s ne respecte plus le contrat des parties d’avatar.', $repositoryClass),
            );
        }
    }

    public function testContractExposesTheTwoQueriesRequiredByTheGrid(): void
    {
        foreach (['findPartByFilters', 'findAllPart'] as $method) {
            self::assertTrue(
                method_exists(AvatarPartModelInterface::class, $method),
                sprintf('Blocage : le contrat Avatar ne fournit plus la méthode %s attendue par la grille.', $method),
            );
        }
    }
}
