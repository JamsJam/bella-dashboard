<?php

namespace App\Controller\Avatar;

use App\Application\Avatar\Services\AvatarResolverService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeleteAvatarPartController extends AbstractController
{
    #[Route('/avatar/{part}/{id}', name: 'app_avatar_part_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function __invoke(Request $request, string $part, int $id, AvatarResolverService $avatarResolverService, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->isCsrfTokenValid('avatar_part_delete', (string) $request->headers->get('X-CSRF-TOKEN', ''))) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid CSRF token.',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $entityClass = $avatarResolverService->resolveEntity($part);
        } catch (\InvalidArgumentException) {
            return $this->json([
                'success' => false,
                'error' => 'Avatar part category not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $avatarPart = $entityManager->find($entityClass, $id);

        if (!is_object($avatarPart)) {
            return $this->json([
                'success' => false,
                'error' => 'Avatar part not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($avatarPart);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'part' => $part,
            'id' => $id,
        ]);
    }
}
