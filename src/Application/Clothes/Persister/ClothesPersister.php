<?php

namespace App\Application\Clothes\Persister;

use App\Application\Clothes\DTO\ClotheImageInput;
use App\Application\Clothes\Services\ClotheService;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use App\Entity\Collections\Collections;
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
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @param list<array{data: array<string, mixed>, images: array<int, mixed>}> $clothes
     */
    public function createForCollection(Collections $collection, array $clothes): void
    {
        foreach ($clothes as $clothe) {
            $this->createClotheForCollection($clothe['data'], $clothe['images'], $collection);
        }

        $this->entityManager->flush();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, mixed> $uploadedImages
     */
    private function createClotheForCollection(array $data, array $uploadedImages, Collections $collection): void
    {
        $description = trim((string) ($data['description'] ?? ''));
        $metaDescription = trim((string) ($data['metadescription'] ?? ''));
        if (mb_strlen($metaDescription) > 180) {
            throw new \InvalidArgumentException('La meta description est limitee a 180 caracteres.');
        }

        $price = (int) ($data['price'] ?? 0);
        if ($price <= 0) {
            throw new \InvalidArgumentException('Le prix du vetement doit etre superieur a 0.');
        }

        $stock = (int) ($data['stock'] ?? 0);
        if ($stock < 0) {
            throw new \InvalidArgumentException('Le stock du vetement ne peut pas etre negatif.');
        }

        $selectedSizes = $data['sizes'] ?? [];
        $sizes = array_values(array_intersect(ClotheService::AVAILABLE_SIZES, is_array($selectedSizes) ? $selectedSizes : []));
        if ($sizes === []) {
            throw new \InvalidArgumentException('Selectionne au moins une taille pour le vetement.');
        }

        $color = $this->resolveClotheColorFromData($data);
        if (!$color instanceof Clothescolor) {
            throw new \InvalidArgumentException('Selectionne une couleur ou cree une nouvelle couleur.');
        }

        $name = $this->createClotheName($collection, $color);
        $images = $this->storeClotheImages($uploadedImages, $name);
        if ($images === []) {
            throw new \InvalidArgumentException('Ajoute au moins une image pour le vetement.');
        }

        $slug = $this->createClotheSlug($collection, $color);
        foreach ($sizes as $sizeName) {
            $size = $this->findOrCreateSize($sizeName);
            $clothe = (new Clothes())
                ->setName($name)
                ->setDescription($description !== '' ? $description : null)
                ->setMetadescription($metaDescription !== '' ? $metaDescription : null)
                ->setPrice($price)
                ->setStock($stock)
                ->setImages(array_map(static fn (ClotheImageInput $image): string => $image->path, $images))
                ->setCollection($collection)
                ->setColor($color)
                ->setSize($size)
                ->setSku($this->createSku($slug, $sizeName))
                ->setSlug($slug)
                ->setStatus('draft')
                ->setIsOnline(false)
                ->setIsBestseller(false)
                ->setIsInCarousel(false)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setEditedAt(new \DateTimeImmutable());

            $this->entityManager->persist($clothe);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveClotheColorFromData(array $data): ?Clothescolor
    {
        $newColorName = trim((string) ($data['newColorName'] ?? ''));

        if ($newColorName !== '') {
            $colorHex = ltrim(trim((string) ($data['newColorHex'] ?? '')), '#');
            if ($colorHex !== '' && !preg_match('/^[a-fA-F0-9]{6}$/', $colorHex)) {
                throw new \InvalidArgumentException('Le code couleur doit etre au format hexadecimal.');
            }

            $existingColor = $this->entityManager->getRepository(Clothescolor::class)->findOneBy(['name' => $newColorName]);
            if ($existingColor instanceof Clothescolor) {
                return $existingColor;
            }

            $color = (new Clothescolor())
                ->setName($newColorName)
                ->setHexa($colorHex !== '' ? strtolower($colorHex) : null)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setEditedAt(new \DateTimeImmutable());

            $this->entityManager->persist($color);

            return $color;
        }

        if ((string) ($data['color'] ?? '') === '__new__') {
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
     * @return list<ClotheImageInput>
     */
    private function storeClotheImages(array $uploadedImages, string $clotheName): array
    {
        $directorySlug = strtolower((string) (new AsciiSlugger())->slug($clotheName));
        $directory = $this->projectDir.'/public/images/upload/clothes/'.$directorySlug;
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create clothe upload directory.');
        }

        $images = [];
        foreach (array_values($uploadedImages) as $position => $image) {
            if (!$image instanceof UploadedFile || !$this->isValidImage($image)) {
                continue;
            }

            $extension = strtolower((string) $image->guessExtension());
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }

            if ($extension === '' || !in_array($extension, self::IMAGE_EXTENSIONS, true)) {
                $extension = strtolower((string) $image->getClientOriginalExtension());
            }

            $filename = sprintf('%02d-%s.%s', $position + 1, bin2hex(random_bytes(4)), $extension);
            $image->move($directory, $filename);
            $images[] = new ClotheImageInput(
                path: '/images/upload/clothes/'.$directorySlug.'/'.$filename,
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

    private function createClotheSlug(Collections $collection, Clothescolor $color): string
    {
        return strtolower((string) (new AsciiSlugger())->slug(sprintf('%s %s', $collection->getName(), $color->getName())));
    }

    private function createClotheName(Collections $collection, Clothescolor $color): string
    {
        $name = sprintf('%s - %s', $collection->getName(), $color->getName());
        if (mb_strlen($name) > 70) {
            throw new \InvalidArgumentException('Le nom genere du vetement est limite a 70 caracteres.');
        }

        return $name;
    }

    private function createSku(string $slug, string $sizeName): string
    {
        return strtoupper(sprintf('%s-%s-%s', $slug, $sizeName, bin2hex(random_bytes(2))));
    }
}
