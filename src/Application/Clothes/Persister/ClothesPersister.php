<?php

namespace App\Application\Clothes\Persister;

use App\Application\Clothes\DTO\ClotheImageInput;
use App\Application\Clothes\Guard\ClotheNameGuard;
use App\Application\Clothes\Services\ClotheService;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Collections\Collections;
use App\Enum\ClotheStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class ClothesPersister
{
    private const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg'];
    private const IMAGE_MIME_TYPES = ['image/png', 'image/jpeg'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClotheNameGuard $clotheNameGuard,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @param list<array{data: array<string, mixed>, images: array<int, mixed>}> $clothes
     */
    public function createForCollection(Collections $collection, array $clothes): void
    {
        $reservedSlugs = [];

        foreach ($clothes as $clothe) {
            $this->createClotheForCollection($clothe['data'], $clothe['images'], $collection, $reservedSlugs);
        }

        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, mixed>    $uploadedImages
     * @param array<string, true>  $reservedSlugs
     */
    private function createClotheForCollection(array $data, array $uploadedImages, Collections $collection, array &$reservedSlugs): void
    {
        $description = trim((string) ($data['description'] ?? ''));
        $price = (int) ($data['price'] ?? 0);
        if ($price <= 0) {
            throw new \InvalidArgumentException('Le prix du vetement doit etre superieur a 0.');
        }

        $name = $this->clotheNameGuard->assertNameAvailable($this->resolveSubmittedName($data));
        $slug = $this->clotheNameGuard->createSlug($name);
        if (isset($reservedSlugs[$slug])) {
            throw new \InvalidArgumentException('Un autre vetement utilise deja ce nom.');
        }

        $reservedSlugs[$slug] = true;
        $images = $this->storeClotheImages($uploadedImages, $name);
        if ([] === $images) {
            throw new \InvalidArgumentException('Ajoute au moins une image pour le vetement.');
        }
        $imagePaths = array_map(static fn (ClotheImageInput $image): string => $image->path, $images);

        $now = new \DateTimeImmutable();
        $clothe = (new Clothes())
            ->setName($name)
            ->setPrice($price)
            ->setCollection($collection)
            ->setCreatedAt($now)
            ->setEditedAt($now);

        $variantPayloads = $this->normalizeVariantPayloads($data);
        if ([] === $variantPayloads) {
            throw new \InvalidArgumentException('Ajoute au moins une variante au vetement.');
        }

        foreach ($variantPayloads as $variantData) {
            $stock = filter_var($variantData['stock'] ?? 0, FILTER_VALIDATE_INT);
            if (false === $stock || $stock < 0) {
                throw new \InvalidArgumentException('Le stock de chaque variante doit etre un entier positif ou nul.');
            }

            $color = $this->resolveClotheColorFromData($variantData);
            if (!$color instanceof Clothescolor) {
                throw new \InvalidArgumentException('Selectionne une couleur pour chaque variante.');
            }

            $sizeName = trim((string) ($variantData['size'] ?? ''));
            if (!in_array($sizeName, ClotheService::AVAILABLE_SIZES, true)) {
                throw new \InvalidArgumentException('Selectionne une taille valide pour chaque variante.');
            }

            $metaDescription = $this->normalizeVariantMetaDescription($variantData['metadescription'] ?? null);
            $size = $this->findOrCreateSize($sizeName);
            $variantName = $this->createVariantName($name, $color, $size);

            $variant = (new ClothesVariant())
                ->setName($variantName)
                ->setSlug($this->createVariantSlug($name, $color))
                ->setStock($stock)
                ->setColor($color)
                ->setSize($size)
                ->setSku($this->createSku($name, $color, $size))
                ->setDescription('' !== $description ? $description : null)
                ->setMetadescription($metaDescription)
                ->setImages($imagePaths)
                ->setHighlightImage($imagePaths[0] ?? null)
                ->setBestsellerImage($imagePaths[0] ?? null)
                ->setPublicationStatus(ClotheStatus::Draft);

            $clothe->addVariant($variant);
        }

        $this->assertUniqueVariants($clothe);

        $this->entityManager->persist($clothe);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<array<string, mixed>>
     */
    private function normalizeVariantPayloads(array $data): array
    {
        $variants = $data['variants'] ?? [];
        if (is_array($variants) && [] !== $variants) {
            $normalizedVariants = [];

            foreach ($variants as $variant) {
                if (!is_array($variant)) {
                    continue;
                }

                $selectedSizes = $variant['sizes'] ?? ($variant['size'] ?? []);
                if (is_string($selectedSizes)) {
                    $selectedSizes = [$selectedSizes];
                }

                $sizes = array_values(array_intersect(
                    ClotheService::AVAILABLE_SIZES,
                    is_array($selectedSizes) ? $selectedSizes : [],
                ));

                foreach ($sizes as $size) {
                    $variant['size'] = $size;
                    $normalizedVariants[] = $variant;
                }
            }

            return $normalizedVariants;
        }

        $selectedSizes = $data['sizes'] ?? [];
        $sizes = array_values(array_intersect(ClotheService::AVAILABLE_SIZES, is_array($selectedSizes) ? $selectedSizes : []));

        return array_map(
            static fn (string $size): array => [
                'color' => $data['color'] ?? null,
                'newColorName' => $data['newColorName'] ?? null,
                'newColorHex' => $data['newColorHex'] ?? null,
                'size' => $size,
                'stock' => $data['stock'] ?? 0,
                'sku' => null,
                'metadescription' => $data['metadescription'] ?? null,
            ],
            $sizes,
        );
    }

    /** @param array<string, mixed> $data */
    private function resolveSubmittedName(array $data): string
    {
        return (string) (($data['name'] ?? '') === '__new__'
            ? ($data['newName'] ?? '')
            : ($data['name'] ?? ''));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveClotheColorFromData(array $data): ?Clothescolor
    {
        $newColorName = trim((string) ($data['newColorName'] ?? ''));

        if ('' !== $newColorName) {
            $colorHex = ltrim(trim((string) ($data['newColorHex'] ?? '')), '#');
            if ('' !== $colorHex && !preg_match('/^[a-fA-F0-9]{6}$/', $colorHex)) {
                throw new \InvalidArgumentException('Le code couleur doit etre au format hexadecimal.');
            }

            $existingColor = $this->entityManager->getRepository(Clothescolor::class)->findOneBy(['name' => $newColorName]);
            if ($existingColor instanceof Clothescolor) {
                return $existingColor;
            }

            $color = (new Clothescolor())
                ->setName($newColorName)
                ->setHexa('' !== $colorHex ? strtolower($colorHex) : null)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setEditedAt(new \DateTimeImmutable());

            $this->entityManager->persist($color);

            return $color;
        }

        if ('__new__' === (string) ($data['color'] ?? '')) {
            return null;
        }

        $colorId = (int) ($data['color'] ?? 0);
        if ($colorId <= 0) {
            return null;
        }

        $color = $this->entityManager->getRepository(Clothescolor::class)->find($colorId);

        return $color instanceof Clothescolor ? $color : null;
    }

    private function findOrCreateSize(string $sizeName): Clothessize
    {
        $size = $this->entityManager->getRepository(Clothessize::class)->findOneBy(['name' => $sizeName]);
        if ($size instanceof Clothessize) {
            return $size;
        }

        $size = (new Clothessize())
            ->setName($sizeName)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setEditedAt(new \DateTimeImmutable());

        $this->entityManager->persist($size);

        return $size;
    }

    /**
     * @param array<int, mixed> $uploadedImages
     *
     * @return list<ClotheImageInput>
     */
    private function storeClotheImages(array $uploadedImages, string $clotheName): array
    {
        $directorySlug = strtolower((string) (new AsciiSlugger())->slug($clotheName));
        $directory = $this->projectDir . '/public/images/upload/clothes/' . $directorySlug;
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create clothe upload directory.');
        }

        $images = [];
        foreach (array_values($uploadedImages) as $position => $image) {
            if (!$image instanceof UploadedFile || !$this->isValidImage($image)) {
                continue;
            }

            $extension = strtolower((string) $image->guessExtension());
            if ('jpeg' === $extension) {
                $extension = 'jpg';
            }

            if ('' === $extension || !in_array($extension, self::IMAGE_EXTENSIONS, true)) {
                $extension = strtolower((string) $image->getClientOriginalExtension());
            }

            $filename = sprintf('%02d-%s.%s', $position + 1, bin2hex(random_bytes(4)), $extension);
            $image->move($directory, $filename);
            $images[] = new ClotheImageInput(
                path: '/images/upload/clothes/' . $directorySlug . '/' . $filename,
                originalName: (string) $image->getClientOriginalName(),
                position: $position,
            );
        }

        return $images;
    }

    private function isValidImage(UploadedFile $image): bool
    {
        $extension = strtolower((string) $image->getClientOriginalExtension());
        $mimeType = (string) $image->getMimeType();

        return in_array($extension, self::IMAGE_EXTENSIONS, true)
            && in_array($mimeType, self::IMAGE_MIME_TYPES, true);
    }

    private function createSku(string $clotheName, Clothescolor $color, Clothessize $size): string
    {
        $slugger = new AsciiSlugger();

        return strtoupper(sprintf(
            '%s-%s-%s',
            (string) $slugger->slug($clotheName),
            (string) $slugger->slug((string) $color->getName()),
            (string) $slugger->slug((string) $size->getName()),
        ));
    }

    private function normalizeVariantMetaDescription(mixed $value): ?string
    {
        $metaDescription = trim((string) $value);
        if (mb_strlen($metaDescription) > 200) {
            throw new \InvalidArgumentException('La meta description est limitee a 200 caracteres.');
        }

        return '' !== $metaDescription ? $metaDescription : null;
    }

    private function createVariantName(string $name, Clothescolor $color, Clothessize $size): string
    {
        return trim(sprintf('%s %s %s', $name, (string) $color->getName(), (string) $size->getName()));
    }

    private function createVariantSlug(string $name, Clothescolor $color): string
    {
        return strtolower((string) (new AsciiSlugger())->slug(trim(sprintf('%s %s', $name, (string) $color->getName()))));
    }

    private function assertUniqueVariants(Clothes $clothe): void
    {
        $combinations = [];
        $skus = [];

        foreach ($clothe->getVariants() as $variant) {
            $colorName = (string) $variant->getColor()?->getName();
            $sizeName = (string) $variant->getSize()?->getName();
            $combinationKey = mb_strtolower($colorName . '|' . $sizeName);
            $skuKey = mb_strtolower((string) $variant->getSku());

            if (isset($combinations[$combinationKey])) {
                throw new \InvalidArgumentException(sprintf('Une variante existe deja pour la couleur %s et la taille %s.', $colorName, $sizeName));
            }

            if (isset($skus[$skuKey])) {
                throw new \InvalidArgumentException(sprintf('Le SKU %s est deja utilise.', (string) $variant->getSku()));
            }

            $existingVariant = $this->entityManager->getRepository(ClothesVariant::class)->findOneBy(['sku' => $variant->getSku()]);
            if ($existingVariant instanceof ClothesVariant && !$clothe->getVariants()->contains($existingVariant)) {
                throw new \InvalidArgumentException(sprintf('Le SKU %s est deja utilise.', (string) $variant->getSku()));
            }

            $combinations[$combinationKey] = true;
            $skus[$skuKey] = true;
        }
    }
}
