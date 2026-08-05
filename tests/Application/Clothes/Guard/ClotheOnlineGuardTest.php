<?php

namespace App\Tests\Application\Clothes\Guard;

use App\Application\Clothes\Guard\ClotheOnlineGuard;
use App\Application\Clothes\Guard\Rules\Publish\HasSeoDescriptionRule;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use PHPUnit\Framework\TestCase;

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

        self::assertTrue($guard->canPublishVariants([$orangeSmall, $orangeMedium])->canPublish());
        self::assertFalse($guard->canPublishVariants([$blueSmall])->canPublish());
    }

    private function variant(string $slug, ?string $metaDescription): ClothesVariant
    {
        return (new ClothesVariant())
            ->setSlug($slug)
            ->setMetadescription($metaDescription)
            ->setStock(1);
    }
}
