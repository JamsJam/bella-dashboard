<?php

namespace App\Application\Clothes\Services\Category;

use App\Application\Clothes\Persister\CategoryPersister;
use App\Application\Clothes\Provider\CategoryProvider\CategoryProvider;
use App\Entity\Category\Category;
use App\Service\FileManagerService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\AsciiSlugger;

final readonly class CategoryManagementService
{
    private const EXTENSIONS = ['png', 'jpg', 'jpeg', 'svg'];
    private const MIME_TYPES = ['image/png', 'image/jpeg', 'image/svg+xml'];

    public function __construct(
        private CategoryProvider $provider,
        private CategoryPersister $persister,
        private FileManagerService $files,
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
    ) {
    }

    public function create(Request $request): Category
    {
        $name = $this->name($request);
        $image = $this->optionalImage($request);
        $now = new \DateTimeImmutable();
        $category = (new Category())
            ->setName($name)
            ->setSlug($this->uniqueSlug($name))
            ->setMetaDescription($this->description($request))
            ->setIsOnline(false)
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $this->persister->save($category);
        if ($image instanceof UploadedFile) {
            $category->setImage($this->storeImage($category, $image));
            $this->persister->save($category);
        }

        return $category;
    }

    public function update(Category $category, Request $request): void
    {
        $name = $this->name($request);
        $image = $this->optionalImage($request);
        $category->setName($name)->setSlug($this->uniqueSlug($name, $category))
            ->setMetaDescription($this->description($request))->setEditedAt(new \DateTimeImmutable());
        if ($image instanceof UploadedFile) {
            $category->setImage($this->storeImage($category, $image));
        }
        $this->persister->save($category);
    }

    public function updateImage(Category $category, Request $request): void
    {
        $image = $request->files->get('illustration');
        if (!$image instanceof UploadedFile) {
            throw new \InvalidArgumentException('Aucune image selectionnee.');
        }
        $this->validateImage($image);
        $category->setImage($this->storeImage($category, $image))->setEditedAt(new \DateTimeImmutable());
        $this->persister->save($category);
    }

    private function name(Request $request): string
    {
        $name = trim((string) $request->request->get('name', ''));
        if ('' === $name) {
            throw new \InvalidArgumentException('Le nom de la categorie est obligatoire.');
        }

        return $name;
    }

    private function description(Request $request): ?string
    {
        return trim((string) $request->request->get('metaDescription', '')) ?: null;
    }

    private function optionalImage(Request $request): ?UploadedFile
    {
        $image = $request->files->get('illustration');
        if (null === $image) {
            return null;
        }
        if (!$image instanceof UploadedFile) {
            throw new \InvalidArgumentException('Image invalide. Formats acceptes : PNG, JPEG, SVG.');
        }
        $this->validateImage($image);

        return $image;
    }

    private function validateImage(UploadedFile $image): void
    {
        if (
            !in_array(strtolower($image->getClientOriginalExtension()), self::EXTENSIONS, true)
            || !in_array((string) $image->getMimeType(), self::MIME_TYPES, true)
        ) {
            throw new \InvalidArgumentException('Image invalide. Formats acceptes : PNG, JPEG, SVG.');
        }
    }

    private function uniqueSlug(string $name, ?Category $current = null): string
    {
        $base = substr(strtolower((string) (new AsciiSlugger())->slug($name)) ?: 'categorie', 0, 60);
        $slug = $base;
        for ($index = 1; $this->provider->slugExists($slug, $current); ++$index) {
            $slug = sprintf('%s-%d', $base, $index);
        }

        return $slug;
    }

    private function storeImage(Category $category, UploadedFile $image): string
    {
        $directory = $this->projectDir . '/public/images/upload/categories/' . $category->getId();
        $this->files->ensureDirectory($directory);
        $extension = strtolower((string) $image->guessExtension());
        $extension = 'jpeg' === $extension ? 'jpg' : $extension;
        if ('' === $extension || !in_array($extension, self::EXTENSIONS, true)) {
            $extension = strtolower($image->getClientOriginalExtension());
        }
        $filename = sprintf('category-%d-%s.%s', $category->getId(), bin2hex(random_bytes(4)), $extension);
        $image->move($directory, $filename);

        return '/images/upload/categories/' . $category->getId() . '/' . $filename;
    }
}
