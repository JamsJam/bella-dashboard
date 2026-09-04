<?php

namespace App\Application\Clothes\Services\Collection;

use App\Application\Clothes\Persister\CollectionPersister;
use App\Entity\Category\Category;
use App\Entity\Collections\Collections;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final class CollectionCreationService
{
    private const ILLUSTRATION_EXTENSIONS = ['png', 'jpg', 'jpeg'];
    private const ILLUSTRATION_MIME_TYPES = ['image/png', 'image/jpeg'];

    public function __construct(
        private readonly CollectionPersister $collectionPersister,
    ) {
    }

    public function createFromRequest(Request $request): Collections
    {
        $name = trim((string) $request->request->get('name', ''));
        if ('' === $name) {
            throw new \InvalidArgumentException('Le nom de la collection est obligatoire.');
        }

        $image = $request->files->get('illustration');
        if (!$image instanceof UploadedFile || !$this->isValidIllustration($image)) {
            throw new \InvalidArgumentException('Image invalide. Formats acceptes : PNG ou JPEG.');
        }

        $category = $this->resolveCategory($request);
        if (!$category instanceof Category) {
            throw new \InvalidArgumentException('Selectionne une categorie ou cree une nouvelle categorie.');
        }

        return $this->collectionPersister->createCollection($name, $category, $image);
    }

    private function resolveCategory(Request $request): ?Category
    {
        $newCategoryName = trim((string) $request->request->get('newCategory', ''));

        if ('' !== $newCategoryName) {
            return $this->collectionPersister->createCategory($newCategoryName);
        }

        if ('__new__' === (string) $request->request->get('category')) {
            return null;
        }

        return $this->collectionPersister->findCategory($request->request->getInt('category'));
    }

    private function isValidIllustration(UploadedFile $image): bool
    {
        $extension = strtolower((string) $image->getClientOriginalExtension());
        $mimeType = (string) $image->getMimeType();

        return in_array($extension, self::ILLUSTRATION_EXTENSIONS, true)
            && in_array($mimeType, self::ILLUSTRATION_MIME_TYPES, true);
    }
}
