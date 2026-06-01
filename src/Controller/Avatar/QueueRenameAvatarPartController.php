<?php

namespace App\Controller\Avatar;

use App\Application\Avatar\Services\AvatarPartRenameQueueService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class QueueRenameAvatarPartController extends AbstractController
{
    #[Route('/avatar/{part}/{id}/rename', name: 'app_avatar_part_queue_rename', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function __invoke(Request $request, string $part, int $id, AvatarPartRenameQueueService $avatarPartRenameQueueService): JsonResponse
    {
        if (!$this->isCsrfTokenValid('avatar_part_queue_rename', (string) $request->headers->get('X-CSRF-TOKEN', ''))) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid CSRF token.',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $avatarTemp = $avatarPartRenameQueueService->queueForRename($part, $id);
        } catch (\InvalidArgumentException) {
            return $this->json([
                'success' => false,
                'error' => 'Avatar part category not found.',
            ], Response::HTTP_NOT_FOUND);
        } catch (\RuntimeException $exception) {
            $statusCode = in_array($exception->getCode(), [
                Response::HTTP_NOT_FOUND,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::HTTP_INTERNAL_SERVER_ERROR,
            ], true) ? $exception->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

            return $this->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], $statusCode);
        }

        return $this->json([
            'success' => true,
            'status' => 'queued_for_rename',
            'avatarTempId' => $avatarTemp->getId(),
            'part' => $part,
            'id' => $id,
        ]);
    }
}
