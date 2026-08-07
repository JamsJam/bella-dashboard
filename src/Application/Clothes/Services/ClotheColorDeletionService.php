<?php

namespace App\Application\Clothes\Services;

use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\ClothesVariant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ClotheColorDeletionService
{
    private string $uploadDirectory;

    public function __construct(
        private EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')]
        string $projectDirectory,
    ) {
        $this->uploadDirectory = $projectDirectory . '/public/images/upload/clothes';
    }

    /** @return array{variants: int, clothes: int, images: int} */
    public function delete(Clothescolor $color): array
    {
        $variants = $color->getVariants()->toArray();
        $clothes = $this->collectClothes($variants);
        $images = $this->collectImages($variants);

        $this->entityManager->wrapInTransaction(function () use ($color, $variants, $clothes): void {
            foreach ($variants as $variant) {
                $clothe = $variant->getClothes();
                $clothe?->removeVariant($variant);
                $color->removeVariant($variant);
                $this->entityManager->remove($variant);
            }

            foreach ($clothes as $clothe) {
                if ($clothe->getVariants()->isEmpty()) {
                    $this->entityManager->remove($clothe);
                }
            }

            $this->entityManager->remove($color);
            $this->entityManager->flush();
        });

        $deletedImages = 0;
        foreach ($images as $image) {
            if ($this->deleteImage($image)) {
                ++$deletedImages;
            }
        }

        return [
            'variants' => count($variants),
            'clothes' => count($clothes),
            'images' => $deletedImages,
        ];
    }

    /**
     * @param list<ClothesVariant> $variants
     *
     * @return list<Clothes>
     */
    private function collectClothes(array $variants): array
    {
        $clothes = [];
        foreach ($variants as $variant) {
            $clothe = $variant->getClothes();
            if ($clothe instanceof Clothes) {
                $clothes[spl_object_id($clothe)] = $clothe;
            }
        }

        return array_values($clothes);
    }

    /** @param list<ClothesVariant> $variants @return list<string> */
    private function collectImages(array $variants): array
    {
        $images = [];
        foreach ($variants as $variant) {
            foreach ($variant->getImages() ?? [] as $image) {
                if (is_string($image)) {
                    $images[$image] = $image;
                }
            }
            foreach ([$variant->getHighlightImage(), $variant->getBestsellerImage()] as $image) {
                if (is_string($image)) {
                    $images[$image] = $image;
                }
            }
        }

        return array_values($images);
    }

    private function deleteImage(string $image): bool
    {
        $prefix = '/images/upload/clothes/';
        if (!str_starts_with($image, $prefix)) {
            return false;
        }

        $relativePath = substr($image, strlen($prefix));
        if ('' === $relativePath || str_contains($relativePath, '..')) {
            return false;
        }

        $path = $this->uploadDirectory . '/' . ltrim($relativePath, '/');
        if (!is_file($path) || !unlink($path)) {
            return false;
        }

        $directory = dirname($path);
        if ($directory !== $this->uploadDirectory && [] === $this->directoryContents($directory)) {
            rmdir($directory);
        }

        return true;
    }

    /** @return list<string> */
    private function directoryContents(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        return array_values(array_diff(scandir($directory) ?: [], ['.', '..']));
    }
}
