<?php

namespace App\Application\Clothes\Services;

use App\Enum\ClotheStatus;
use App\Application\Clothes\DTO\VariantGroupInput;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Clothes\Clothescolor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;

final readonly class ClotheVariantFactory
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /** @return list<ClothesVariant> */
    public function createGroup(Clothes $clothe, VariantGroupInput $input): array
    {
        $color = $this->resolveColor($input);
        $images = $this->storeImages($input->images, (string) $clothe->getName(), (string) $color->getName());
        $slugger = new AsciiSlugger();
        $slug = strtolower((string) $slugger->slug(trim($clothe->getName().' '.$color->getName())));
        $created = [];
        $now = new \DateTimeImmutable();

        foreach ($input->sizes as $size) {
            foreach ($clothe->getVariants() as $existing) {
                if ($existing->getColor()?->getName() === $color->getName() && $existing->getSize()?->getName() === $size->getName()) {
                    throw new \DomainException(sprintf('La variante %s %s existe déjà.', $color->getName(), $size->getName()));
                }
            }

            $variant = (new ClothesVariant())
                ->setName(trim(sprintf('%s %s %s', $clothe->getName(), $color->getName(), $size->getName())))
                ->setSlug($slug)
                ->setColor($color)
                ->setSize($size)
                ->setSku(strtoupper(sprintf('%s-%s', $slug, (string) $slugger->slug((string) $size->getName()))))
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

    private function resolveColor(VariantGroupInput $input): Clothescolor
    {
        if ($input->color instanceof Clothescolor) {
            return $input->color;
        }

        $name = trim((string) $input->newColorName);
        $existing = $this->entityManager->getRepository(Clothescolor::class)->findOneBy(['name' => $name]);
        if ($existing instanceof Clothescolor) {
            return $existing;
        }

        $now = new \DateTimeImmutable();
        $color = (new Clothescolor())
            ->setName($name)
            ->setHexa(strtolower(ltrim((string) $input->newColorHex, '#')))
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $this->entityManager->persist($color);

        return $color;
    }

    /** @param list<UploadedFile> $files @return list<string> */
    private function storeImages(array $files, string $clotheName, string $colorName): array
    {
        $slugger = new AsciiSlugger();
        $directoryName = strtolower((string) $slugger->slug($clotheName.'-'.$colorName));
        $directory = $this->projectDir.'/public/images/upload/clothes/'.$directoryName;
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Impossible de créer le dossier des images.');
        }

        $paths = [];
        foreach ($files as $position => $file) {
            $extension = $file->guessExtension() ?: 'jpg';
            $filename = sprintf('%02d-%s.%s', $position + 1, bin2hex(random_bytes(5)), $extension);
            $file->move($directory, $filename);
            $paths[] = '/images/upload/clothes/'.$directoryName.'/'.$filename;
        }

        return $paths;
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
