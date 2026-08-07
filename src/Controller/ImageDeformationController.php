<?php

namespace App\Controller;

use App\Application\ImageDeformation\ImageDeformationJobStorage;
use App\Application\ImageDeformation\ImageDeformationProcessor;
use App\Message\ImageDeformation\DeformImageMessage;
use App\Service\BreadscrumbsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/avatar/image-deformation')]
final class ImageDeformationController extends AbstractController
{
    #[Route('', name: 'app_avatar_deformation', methods: ['GET'])]
    public function index(Request $request, BreadscrumbsService $breadscrumbs): Response
    {
        return $this->render('avatar/image_deformation.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve((string) $request->attributes->get('_route'), currentLabel: 'Déformer une image'),
        ]);
    }

    #[Route('', name: 'app_avatar_image_deformation_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        MessageBusInterface $messageBus,
        ImageDeformationJobStorage $storage,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('image_deformation', (string) $request->request->get('_csrf_token', ''))) {
            return $this->json(['error' => 'Token CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $file = $request->files->get('image');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return $this->json(['error' => 'Aucune image valide n’a été reçue.'], Response::HTTP_BAD_REQUEST);
        }

        if (false === $file->getSize() || $file->getSize() > 20 * 1024 * 1024) {
            return $this->json(['error' => 'L’image ne doit pas dépasser 20 Mo.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $imageInfo = @getimagesize($file->getPathname());
        if (false === $imageInfo || ($imageInfo[2] ?? null) !== IMAGETYPE_PNG) {
            return $this->json(['error' => 'Seules les images PNG sont acceptées.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if ($imageInfo[0] * $imageInfo[1] > ImageDeformationProcessor::MAX_PIXELS) {
            return $this->json(['error' => 'Les dimensions de l’image sont trop importantes.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $jobId = bin2hex(random_bytes(16));
        $jobDirectory = $storage->createDirectory($jobId);

        try {
            $file->move($jobDirectory, 'source.png');
            $storage->writeStatus($jobId, 'pending');
            $messageBus->dispatch(new DeformImageMessage($jobId));
        } catch (\Throwable $exception) {
            $storage->delete($jobId);
            throw $exception;
        }

        return $this->json([
            'jobId' => $jobId,
            'status' => 'pending',
            'statusUrl' => $this->generateUrl('app_avatar_image_deformation_status', ['id' => $jobId]),
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/{id}/status', name: 'app_avatar_image_deformation_status', requirements: ['id' => '[a-f0-9]{32}'], methods: ['GET'])]
    public function status(string $id, ImageDeformationJobStorage $storage): JsonResponse
    {
        $payload = $storage->readStatus($id);
        if (null === $payload) {
            return $this->json(['error' => 'Traitement introuvable ou expiré.'], Response::HTTP_NOT_FOUND);
        }
        if ('completed' === $payload['status']) {
            $payload['downloadUrl'] = $this->generateUrl('app_avatar_image_deformation_download', ['id' => $id]);
        }

        return $this->json($payload);
    }

    #[Route('/{id}/download', name: 'app_avatar_image_deformation_download', requirements: ['id' => '[a-f0-9]{32}'], methods: ['GET'])]
    public function download(string $id, ImageDeformationJobStorage $storage): BinaryFileResponse
    {
        $status = $storage->readStatus($id);
        $path = $storage->resultPath($id);
        if (($status['status'] ?? null) !== 'completed' || !is_file($path)) {
            throw $this->createNotFoundException('L’image finale n’est pas disponible.');
        }

        return $this->file($path, 'image-' . $id . '.png', ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }
}
