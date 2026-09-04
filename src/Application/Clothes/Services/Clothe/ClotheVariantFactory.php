<?php

namespace App\Application\Clothes\Services\Clothe;

use App\Application\Clothes\DTO\VariantGroupInput;
use App\Application\Clothes\Exception\DuplicateClotheVariantException;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\ClothesVariant;
use App\Enum\ClotheStatus;
use Symfony\Component\String\Slugger\AsciiSlugger;

final readonly class ClotheVariantFactory
{
    /**
     * @param list<string> $images
     *
     * @return list<ClothesVariant>
     */
    public function createGroup(
        Clothes $clothe,
        VariantGroupInput $input,
        Clothescolor $color,
        array $images,
    ): array {
        $slugger = new AsciiSlugger();
        $slug = strtolower((string) $slugger->slug(trim(sprintf(
            '%s %s',
            $clothe->getName(),
            $color->getName(),
        ))));
        $created = [];
        $now = new \DateTimeImmutable();

        foreach ($input->sizes as $size) {
            $this->assertVariantDoesNotExist(
                $clothe,
                $color,
                (string) $size->getName(),
            );
            $variant = (new ClothesVariant())
                ->setName(trim(sprintf(
                    '%s %s %s',
                    $clothe->getName(),
                    $color->getName(),
                    $size->getName(),
                )))
                ->setSlug($slug)
                ->setColor($color)
                ->setSize($size)
                ->setSku(strtoupper(sprintf(
                    '%s-%s',
                    $slug,
                    (string) $slugger->slug((string) $size->getName()),
                )))
                ->setStock(0)
                ->setDescription($this->nullable($input->description))
                ->setMetadescription($this->nullable($input->metaDescription))
                ->setImages($images)
                ->setHighlightImage($images[0] ?? null)
                ->setBestsellerImage($images[0] ?? null)
                ->setPublicationStatus(ClotheStatus::Draft)
                ->setCreatedAt($now)
                ->setEditedAt($now);
            $clothe->addVariant($variant);
            $created[] = $variant;
        }

        return $created;
    }

    private function assertVariantDoesNotExist(
        Clothes $clothe,
        Clothescolor $color,
        string $sizeName,
    ): void {
        foreach ($clothe->getVariants() as $existing) {
            if (
                $existing->getColor()?->getName() === $color->getName()
                && $existing->getSize()?->getName() === $sizeName
            ) {
                throw new DuplicateClotheVariantException(sprintf(
                    'La variante %s %s existe déjà.',
                    $color->getName(),
                    $sizeName,
                ));
            }
        }
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }
}
