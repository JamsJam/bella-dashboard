<?php

namespace App\Controller\Avatar;

use App\Application\Avatar\Mapper\AvatarFilterMapper;
use App\Application\Avatar\Resolver\AvatarRenameDestinationResolver;
use App\Entity\AvatarTemp;
use App\Message\Avatar\RenameAvatarMessage;
use App\Notifier\Services\FlashService;
use App\Service\BreadscrumbsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/avatar/rename')]
final class RenameAvatarController extends AbstractController
{
    #[Route('', name: 'app_avatar_rename', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        BreadscrumbsService $breadscrumbs,
        CsrfTokenManagerInterface $csrfTokenManager,
        AvatarFilterMapper $avatarFilterMapper,
    ): Response {
        $avatarTemps = $entityManager->getRepository(AvatarTemp::class)->findBy(
            ['status' => 'uploaded'],
            ['createdAt' => 'ASC'],
        );

        

        return $this->render('avatar/rename.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve((string) $request->attributes->get('_route')),
            'avatars' => array_map(fn (AvatarTemp $avatarTemp): array => $this->mapAvatarTemp($avatarTemp), $avatarTemps),
            'partLabels' => $avatarFilterMapper->getPartLabels(),
            'filter_url' => $this->generateUrl('app_search_avatar_filters'),
            'check_name_url' => $this->generateUrl('app_avatar_rename_check_name'),
            'csrf_token' => $csrfTokenManager->getToken('avatar_rename')->getValue(),
            'delete_csrf_token' => $csrfTokenManager->getToken('avatar_temp_delete')->getValue(),
        ]);
    }

    #[Route('/check-name', name: 'app_avatar_rename_check_name', methods: ['GET'])]
    public function checkName(
        Request $request,
        AvatarRenameDestinationResolver $destinationResolver,
    ): Response {
        $newName = (string) $request->query->get('name', '');
        $category = (string) $request->query->get('category', '');
        $filters = json_decode((string) $request->query->get('filters', '{}'), true);

        if (!$this->isSafeAvatarName($newName) || $category === '' || !is_array($filters)) {
            return $this->json(['available' => false, 'error' => 'Nom invalide.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $message = new RenameAvatarMessage(
                avatarTempId: 0,
                newName: $newName,
                category: $category,
                filters: $filters,
            );
            $webDirectory = $destinationResolver->resolveWebDirectory($message);
            $absolutePath = $destinationResolver->resolveAbsoluteDirectory($webDirectory).'/'.$newName;
        } catch (\Throwable) {
            return $this->json(['available' => false, 'error' => 'Impossible de verifier ce nom.'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_file($absolutePath)) {
            return $this->json(['available' => true]);
        }

        return $this->json([
            'available' => false,
            'previewUrl' => $webDirectory.'/'.$newName,
            'message' => 'Un element existe deja avec ce nom.',
        ]);
    }

    #[Route('', name: 'app_avatar_rename_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        MessageBusInterface $messageBus,
        EntityManagerInterface $entityManager,
        FlashService $flashService,
    ): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('avatar_rename', (string) $request->request->get('_csrf_token', ''))) {
            $flashService->error('Token CSRF invalide.');

            return $this->redirectToRoute('app_avatar_rename');
        }

        $renames = json_decode((string) $request->request->get('renames', '[]'), true);
        if (!is_array($renames)) {
            $flashService->error('Payload de renommage invalide.');

            return $this->redirectToRoute('app_avatar_rename');
        }

        $dispatched = 0;
        foreach ($renames as $rename) {
            if (!$this->isRenamePayloadValid($rename)) {
                continue;
            }

            $avatarTemp = $entityManager->find(AvatarTemp::class, (int) $rename['avatarTempId']);
            if (!$avatarTemp instanceof AvatarTemp || $avatarTemp->getStatus() !== 'uploaded') {
                continue;
            }

            $avatarTemp->setStatus('renaming');
            $entityManager->flush();

            $messageBus->dispatch(new RenameAvatarMessage(
                avatarTempId: (int) $rename['avatarTempId'],
                newName: (string) $rename['newName'],
                category: (string) $rename['category'],
                filters: $rename['filters'],
                replaceExisting: (bool) ($rename['replaceExisting'] ?? false),
            ));
            $dispatched++;
        }

        if ($dispatched > 0) {
            $flashService->success(sprintf('%d renommage(s) envoye(s) au traitement.', $dispatched));
        } else {
            $flashService->error('Aucun renommage valide a traiter.');
        }

        return $this->redirectToRoute('app_avatar_rename');
    }

    #[Route('/{id}/delete', name: 'app_avatar_rename_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        AvatarTemp $avatarTemp,
        Request $request,
        EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ): Response {
        if (!$this->isCsrfTokenValid('avatar_temp_delete', (string) $request->headers->get('X-CSRF-TOKEN', ''))) {
            return $this->json(['success' => false, 'error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        if ($avatarTemp->getStatus() !== 'uploaded') {
            return $this->json(['success' => false, 'error' => 'Cette image ne peut pas etre supprimee.'], Response::HTTP_CONFLICT);
        }

        $tempPath = $avatarTemp->getTempPath();
        if ($tempPath !== null && is_file($tempPath) && $this->isPathInside($tempPath, $projectDir.'/var/avatar-temp')) {
            @unlink($tempPath);
            @rmdir(dirname($tempPath));
        }

        $entityManager->remove($avatarTemp);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }

    private function mapAvatarTemp(AvatarTemp $avatarTemp): array
    {
        return [
            'id' => $avatarTemp->getId(),
            'originalName' => $avatarTemp->getOriginalName(),
            'storedName' => $avatarTemp->getStoredName(),
            'preview' => $this->generateUrl('app_avatar_rename_preview', ['id' => $avatarTemp->getId()]),
            'deleteUrl' => $this->generateUrl('app_avatar_rename_delete', ['id' => $avatarTemp->getId()]),
        ];
    }

    #[Route('/preview/{id}', name: 'app_avatar_rename_preview', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function preview(
        AvatarTemp $avatarTemp,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ): Response {
        if ($avatarTemp->getStatus() !== 'uploaded') {
            throw $this->createNotFoundException('Avatar temporary image not found.');
        }

        $path = $avatarTemp->getTempPath();

        if ($path === null || !is_file($path) || !$this->isPathInside($path, $projectDir.'/var/avatar-temp')) {
            throw $this->createNotFoundException('Avatar temporary image not found.');
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $avatarTemp->getMimeType() ?: 'image/png');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $avatarTemp->getOriginalName() ?: 'avatar-preview.png',
        );
        $response->setMaxAge(300);
        $response->setPrivate();

        return $response;
    }

    private function isPathInside(string $path, string $allowedRoot): bool
    {
        $allowedRoot = rtrim($allowedRoot, '/').'/';
        $directory = is_dir($path) ? $path : dirname($path);

        $realAllowedRoot = realpath($allowedRoot);
        $realDirectory = realpath($directory);

        return $realAllowedRoot !== false
            && $realDirectory !== false
            && str_starts_with($realDirectory.'/', rtrim($realAllowedRoot, '/').'/');
    }

    private function isRenamePayloadValid(mixed $rename): bool
    {
        if (!is_array($rename)) {
            return false;
        }

        return isset($rename['avatarTempId'], $rename['newName'], $rename['category'], $rename['filters'])
            && is_numeric($rename['avatarTempId'])
            && is_string($rename['newName'])
            && is_string($rename['category'])
            && is_array($rename['filters']);
    }

    private function isSafeAvatarName(string $newName): bool
    {
        return preg_match('/^[A-Za-z0-9_-]+\.png$/', $newName) === 1
            && !str_contains($newName, '/')
            && !str_contains($newName, '\\')
            && !str_contains($newName, '..');
    }
}
