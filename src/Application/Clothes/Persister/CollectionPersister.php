<?php

namespace App\Application\Clothes\Persister;

use App\Entity\Category\Category;
use App\Entity\Collections\Collections;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class CollectionPersister
{
    private const ILLUSTRATION_EXTENSIONS = ['png', 'jpg', 'jpeg'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function findCategory(int $id): ?Category
    {
        if ($id <= 0) {
            return null;
        }

        $category = $this->entityManager->getRepository(Category::class)->find($id);

        return $category instanceof Category ? $category : null;
    }

    public function createCategory(string $name): Category
    {
        $category = (new Category())
            ->setName($name)
            ->setSlug($this->createUniqueCategorySlug($name))
            ->setIsOnline(false)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setEditedAt(new \DateTimeImmutable());

        $this->entityManager->persist($category);

        return $category;
    }

    public function createCollection(string $name, Category $category, UploadedFile $image): Collections
    {
        $collection = (new Collections())
            ->setName($name)
            ->setCategory($category)
            ->setIsOnline(false)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setEditedAt(new \DateTimeImmutable());

        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        $collection->setImage($this->storeIllustration($collection, $image));
        $this->entityManager->flush();

        return $collection;
    }

    private function storeIllustration(Collections $collection, UploadedFile $image): string
    {
        $directory = $this->projectDir . '/public/images/upload/collections/' . $collection->getId();
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create collection upload directory.');
        }

        $extension = strtolower((string) $image->guessExtension());
        if ('jpeg' === $extension) {
            $extension = 'jpg';
        }

        if ('' === $extension || !in_array($extension, self::ILLUSTRATION_EXTENSIONS, true)) {
            $extension = strtolower((string) $image->getClientOriginalExtension());
        }

        $filename = sprintf('collection-%d-%s.%s', $collection->getId(), bin2hex(random_bytes(4)), $extension);
        $image->move($directory, $filename);

        return '/images/upload/collections/' . $collection->getId() . '/' . $filename;
    }

    private function createUniqueCategorySlug(string $name): string
    {
        $baseSlug = strtolower((string) (new AsciiSlugger())->slug($name));
        $baseSlug = substr('' !== $baseSlug ? $baseSlug : 'categorie', 0, 60);
        $slug = $baseSlug;
        $index = 1;

        while ($this->entityManager->getRepository(Category::class)->findOneBy(['slug' => $slug]) instanceof Category) {
            $slug = sprintf('%s-%d', $baseSlug, $index);
            ++$index;
        }

        return $slug;
    }
}
