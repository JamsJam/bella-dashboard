<?php

namespace App\Tests\Clothes\Unit\Guard;

use App\Application\Clothes\Guard\ClotheOnlineGuard;
use App\Application\Clothes\Guard\Rules\Publish\HasSeoDescriptionRule;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('clothes')]
#[Group('unit')]
/** Vérifie que les gardes de publication restent limitées au groupe demandé. */
final class ClotheOnlineGuardTest extends TestCase
{
    public function testSeoValidationIsLimitedToTheRequestedSlugGroup(): void
    {
        $clothe = new Clothes();
        $orangeSmall = $this->variant('keyssie-orange', 'Description orange');
        $orangeMedium = $this->variant('keyssie-orange', 'Description orange');
        $blueSmall = $this->variant('keyssie-blue', null);
        $clothe->addVariant($orangeSmall);
        $clothe->addVariant($orangeMedium);
        $clothe->addVariant($blueSmall);

        $guard = new ClotheOnlineGuard([new HasSeoDescriptionRule()]);

        self::assertTrue(
            $guard->canPublishVariants([$orangeSmall, $orangeMedium])->canPublish(),
            'Blocage : un groupe complet est refusé à cause d’une autre couleur incomplète.',
        );
        self::assertFalse(
            $guard->canPublishVariants([$blueSmall])->canPublish(),
            'Blocage : un groupe sans métadescription passe la garde SEO.',
        );
    }

    private function variant(string $slug, ?string $metaDescription): ClothesVariant
    {
        return (new ClothesVariant())
            ->setSlug($slug)
            ->setMetadescription($metaDescription)
            ->setStock(1);
    }
}
