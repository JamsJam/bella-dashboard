<?php

namespace App\Controller\Avatar;

use App\Application\Avatar\Services\AvatarResolverService;
use App\Application\Avatar\Services\AvatarUploadedFileService;
use App\Service\LoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteAvatarPartController extends AbstractController
{
    #[Route('/avatar/{part}/{id}', name: 'app_avatar_part_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function __invoke(
        Request $request,
        string $part,
        int $id,
        AvatarResolverService $avatarResolverService,
        EntityManagerInterface $entityManager,
        LoggerService $logger,
        AvatarUploadedFileService $uploadedFileService,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('avatar_part_delete', (string) $request->headers->get('X-CSRF-TOKEN', ''))) {
            $logger->warning('Invalid CSRF token for avatar part deletion.', [
                'part' => $part,
                'id' => $id,
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Invalid CSRF token.',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $entityClass = $avatarResolverService->resolveEntity($part);
        } catch (\InvalidArgumentException $exception) {
            $logger->exception($exception, 'Avatar part category not found for deletion.', [
                'part' => $part,
                'id' => $id,
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Avatar part category not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $avatarPart = $entityManager->find($entityClass, $id);

        if (!is_object($avatarPart)) {
            $logger->warning('Avatar part not found for deletion.', [
                'part' => $part,
                'id' => $id,
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Avatar part not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $imagePaths = $this->extractImagePaths($avatarPart);

        try {
            $entityManager->remove($avatarPart);
            $entityManager->flush();
        } catch (\Throwable $exception) {
            $logger->exception($exception, 'Unable to delete avatar part.', [
                'part' => $part,
                'id' => $id,
            ]);

            return $this->json([
                'success' => false,
                'error' => 'Impossible de supprimer cette pièce d’avatar.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        foreach ($imagePaths as $imagePath) {
            try {
                $uploadedFileService->deleteFromWebPath($imagePath);
            } catch (\Throwable $exception) {
                $logger->exception($exception, 'Avatar part deleted but its image could not be deleted.', [
                    'part' => $part,
                    'id' => $id,
                    'image' => $imagePath,
                ]);
            }
        }

        $logger->info('Avatar part deleted.', [
            'part' => $part,
            'id' => $id,
        ]);

        return $this->json([
            'success' => true,
            'part' => $part,
            'id' => $id,
        ]);
    }

    /**
     * @return list<string>
     */
    private function extractImagePaths(object $avatarPart): array
    {
        if (method_exists($avatarPart, 'getImages')) {
            $images = $avatarPart->getImages();

            return is_array($images)
                ? array_values(array_unique(array_filter($images, 'is_string')))
                : [];
        }

        if (method_exists($avatarPart, 'getImage')) {
            $image = $avatarPart->getImage();

            return is_string($image) && '' !== $image ? [$image] : [];
        }

        return [];
    }
}
