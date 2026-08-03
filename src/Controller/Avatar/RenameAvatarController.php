<?php

namespace App\Controller\Avatar;

use App\Application\Avatar\Mapper\AvatarFilterMapper;
use App\Application\Avatar\Mapper\AvatarRenameFilterMapper;
use App\Application\Avatar\Services\AvatarValidatedFilterValueService;
use App\Application\Avatar\Workflow\AvatarRenameValidationContext;
use App\Application\Avatar\Workflow\AvatarRenameGuardContextStore;
use App\Application\Avatar\Workflow\AvatarRenameWorkflow;
use App\Application\Avatar\Workflow\Guard\AvatarOverwriteAuthorizationGuard;
use App\Entity\AvatarTemp;
use App\Message\Avatar\RenameAvatarMessage;
use App\Notifier\Services\FlashService;
use App\Service\BreadscrumbsService;
use App\Service\LoggerService;
use Doctrine\DBAL\LockMode;
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
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/avatar/rename')]
final class RenameAvatarController extends AbstractController
{
    #[Route('', name: 'app_avatar_rename', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        BreadscrumbsService $breadscrumbs,
        AvatarFilterMapper $avatarFilterMapper,
    ): Response {
        $avatarTemps = $entityManager->getRepository(AvatarTemp::class)->findBy(
            ['status' => [AvatarRenameWorkflow::PLACE_UPLOADED, AvatarRenameWorkflow::PLACE_VALIDATED, AvatarRenameWorkflow::PLACE_ERROR]],
            ['createdAt' => 'ASC'],
        );

        return $this->render('avatar/rename.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve((string) $request->attributes->get('_route')),
            'avatars' => array_map(fn (AvatarTemp $avatarTemp): array => $this->mapAvatarTemp($avatarTemp), $avatarTemps),
            'partLabels' => array_filter(
                $avatarFilterMapper->getPartLabels(),
                static fn (string $part): bool => $part !== 'accessory',
                ARRAY_FILTER_USE_KEY,
            ),
            'filter_url' => $this->generateUrl('app_search_avatar_filters'),
        ]);
    }

    #[Route('/{id}/validate', name: 'app_avatar_rename_validate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function validateName(
        AvatarTemp $avatarTemp,
        Request $request,
        AvatarRenameFilterMapper $renameFilterMapper,
        EntityManagerInterface $entityManager,
        #[Autowire(service: 'state_machine.avatar_rename')]
        WorkflowInterface $workflow,
        LoggerService $logger,
        AvatarRenameGuardContextStore $guardContextStore,
        FlashService $flashService,
        AvatarValidatedFilterValueService $validatedFilterValueService,
    ): Response {
        if (!$this->isCsrfTokenValid('avatar_rename_validate_'.$avatarTemp->getId(), (string) $request->request->get('_csrf_token', ''))) {
            $flashService->error('Token CSRF invalide.');

            return $this->json(['error' => 'Token CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        $newName = (string) $request->request->get('name', '');
        $category = (string) $request->request->get('category', '');
        $filters = json_decode((string) $request->request->get('filters', '{}'), true);
        $authorization = filter_var($request->request->get('authorization', false), FILTER_VALIDATE_BOOL);

        if (!$this->isSafeAvatarName($newName) || $category === '' || !is_array($filters)) {
            $logger->warning('Invalid avatar rename check payload.', [
                'name' => $newName,
                'category' => $category,
            ]);
            $flashService->error('Nom invalide.');

            return $this->json(['error' => 'Nom invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $missingFilters = $this->getMissingRequiredFilters($category, $filters, $renameFilterMapper);
        if ($missingFilters !== []) {
            $message = sprintf(
                'Tous les paramètres sont obligatoires pour renommer cet avatar. Paramètre(s) manquant(s) : %s.',
                implode(', ', $missingFilters),
            );
            $logger->warning('Avatar rename blocked by missing required filters.', [
                'category' => $category,
                'missing_filters' => $missingFilters,
            ]);
            $flashService->error($message);

            return $this->json(
                ['error' => $message],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $context = new AvatarRenameValidationContext($newName, $category, $filters, $authorization);
        $guardContextStore->setValidation($avatarTemp, $context);

        try {
            $workflow->apply($avatarTemp, AvatarRenameWorkflow::TRANSITION_VALIDATE, ['validation' => $context]);
        } catch (NotEnabledTransitionException $exception) {
            foreach ($exception->getTransitionBlockerList() as $blocker) {
                if ($blocker->getCode() === AvatarOverwriteAuthorizationGuard::BLOCKER_TARGET_ALREADY_EXISTS) {
                    $html = $this->renderView('avatar/_rename_confirmation_modal.html.twig', [
                        'avatar' => $avatarTemp,
                        'name' => $newName,
                        'category' => $category,
                        'filters' => json_encode($filters, JSON_THROW_ON_ERROR),
                        'previewUrl' => $context->previewUrl(),
                    ]);

                    return new Response(
                        sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
                        Response::HTTP_CONFLICT,
                        ['Content-Type' => 'text/vnd.turbo-stream.html'],
                    );
                }
            }

            $flashService->error('Transition de validation refusée.');

            return $this->json(['error' => 'Transition de validation refusée.'], Response::HTTP_CONFLICT);
        } finally {
            $guardContextStore->clearValidation($avatarTemp);
        }

        $validatedFilterValueService->persistNewValues($category, $filters);
        $avatarTemp->setFinalName($newName);
        $entityManager->flush();

        return $this->json(['success' => true, 'status' => $avatarTemp->getStatus(), 'name' => $newName]);
    }

    #[Route('', name: 'app_avatar_rename_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        MessageBusInterface $messageBus,
        EntityManagerInterface $entityManager,
        FlashService $flashService,
        LoggerService $logger,
        #[Autowire(service: 'state_machine.avatar_rename')]
        WorkflowInterface $workflow,
    ): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('avatar_rename', (string) $request->request->get('_csrf_token', ''))) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for avatar rename submit.');

            return $this->redirectToRoute('app_avatar_rename');
        }

        $renames = json_decode((string) $request->request->get('renames', '[]'), true);
        if (!is_array($renames)) {
            $flashService->error('Payload de renommage invalide.');
            $logger->warning('Invalid avatar rename payload.');

            return $this->redirectToRoute('app_avatar_rename');
        }

        $dispatched = 0;
        foreach ($renames as $rename) {
            if (!$this->isRenamePayloadValid($rename)) {
                continue;
            }

            $avatarTempId = (int) $rename['avatarTempId'];
            $started = $entityManager->wrapInTransaction(function (EntityManagerInterface $manager) use ($avatarTempId, $workflow): bool {
                $avatarTemp = $manager->find(AvatarTemp::class, $avatarTempId, LockMode::PESSIMISTIC_WRITE);
                if (!$avatarTemp instanceof AvatarTemp || $avatarTemp->getStatus() !== AvatarRenameWorkflow::PLACE_VALIDATED) {
                    return false;
                }

                $workflow->apply($avatarTemp, AvatarRenameWorkflow::TRANSITION_START_RENAMING);

                return true;
            });

            if (!$started) {
                continue;
            }

            $messageBus->dispatch(new RenameAvatarMessage(
                avatarTempId: $avatarTempId,
            ));
            $dispatched++;
        }

        if ($dispatched > 0) {
            $flashService->success(sprintf('%d renommage(s) envoye(s) au traitement.', $dispatched));
            $logger->info('Avatar renames dispatched.', [
                'dispatched_count' => $dispatched,
            ]);
        } else {
            $flashService->error('Aucun renommage valide a traiter.');
            $logger->warning('No valid avatar rename to dispatch.');
        }

        return $this->redirectToRoute('app_avatar_rename');
    }

    #[Route('/{id}/retry', name: 'app_avatar_rename_retry', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function retry(
        AvatarTemp $avatarTemp,
        Request $request,
        EntityManagerInterface $entityManager,
        #[Autowire(service: 'state_machine.avatar_rename')]
        WorkflowInterface $workflow,
    ): Response {
        if (!$this->isCsrfTokenValid('avatar_rename_retry_'.$avatarTemp->getId(), (string) $request->request->get('_csrf_token', ''))) {
            return $this->json(['error' => 'Token CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        if (!$workflow->can($avatarTemp, AvatarRenameWorkflow::TRANSITION_RETRY)) {
            return $this->json(['error' => 'Cette image ne peut pas être relancée.'], Response::HTTP_CONFLICT);
        }

        $workflow->apply($avatarTemp, AvatarRenameWorkflow::TRANSITION_RETRY);
        $avatarTemp->setFinalName(null);
        $entityManager->flush();

        if ($request->getPreferredFormat() === 'json') {
            return $this->json(['success' => true, 'status' => $avatarTemp->getStatus()]);
        }

        return $this->redirectToRoute('app_avatar_rename');
    }

    #[Route('/{id}/cancel-validation', name: 'app_avatar_rename_cancel_validation', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancelValidation(
        AvatarTemp $avatarTemp,
        Request $request,
        EntityManagerInterface $entityManager,
        #[Autowire(service: 'state_machine.avatar_rename')]
        WorkflowInterface $workflow,
    ): Response {
        if (!$this->isCsrfTokenValid('avatar_rename_cancel_validation_'.$avatarTemp->getId(), (string) $request->request->get('_csrf_token', ''))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        if (!$workflow->can($avatarTemp, AvatarRenameWorkflow::TRANSITION_CANCEL_VALIDATION)) {
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'status' => $avatarTemp->getStatus(),
                    'error' => 'Transition d’annulation refusée.',
                ], Response::HTTP_CONFLICT);
            }

            return $this->redirectToRoute('app_avatar_rename');
        }

        $workflow->apply($avatarTemp, AvatarRenameWorkflow::TRANSITION_CANCEL_VALIDATION);
        $avatarTemp->setFinalName(null);
        $entityManager->flush();

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true, 'status' => $avatarTemp->getStatus()]);
        }

        return $this->redirectToRoute('app_avatar_rename');
    }

    #[Route('/{id}/delete', name: 'app_avatar_rename_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        AvatarTemp $avatarTemp,
        Request $request,
        EntityManagerInterface $entityManager,
        LoggerService $logger,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ): Response {
        if (!$this->isCsrfTokenValid('avatar_temp_delete', (string) $request->headers->get('X-CSRF-TOKEN', ''))) {
            $logger->warning('Invalid CSRF token for avatar temp deletion.', [
                'avatar_temp_id' => $avatarTemp->getId(),
            ]);

            return $this->json(['success' => false, 'error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        if ($avatarTemp->getStatus() !== 'uploaded') {
            $logger->warning('Avatar temp deletion rejected for current status.', [
                'avatar_temp_id' => $avatarTemp->getId(),
                'status' => $avatarTemp->getStatus(),
            ]);

            return $this->json(['success' => false, 'error' => 'Cette image ne peut pas etre supprimee.'], Response::HTTP_CONFLICT);
        }

        $tempPath = $avatarTemp->getTempPath();
        if ($tempPath !== null && is_file($tempPath) && $this->isPathInside($tempPath, $projectDir.'/var/avatar-temp')) {
            @unlink($tempPath);
            @rmdir(dirname($tempPath));
        }

        $entityManager->remove($avatarTemp);
        $entityManager->flush();
        $logger->info('Avatar temp deleted.', [
            'avatar_temp_id' => $avatarTemp->getId(),
        ]);

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
            'validateUrl' => $this->generateUrl('app_avatar_rename_validate', ['id' => $avatarTemp->getId()]),
            'retryUrl' => $this->generateUrl('app_avatar_rename_retry', ['id' => $avatarTemp->getId()]),
            'cancelValidationUrl' => $this->generateUrl('app_avatar_rename_cancel_validation', ['id' => $avatarTemp->getId()]),
            'status' => $avatarTemp->getStatus(),
            'finalName' => $avatarTemp->getFinalName(),
        ];
    }

    #[Route('/preview/{id}', name: 'app_avatar_rename_preview', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function preview(
        AvatarTemp $avatarTemp,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ): Response {
        if (!in_array($avatarTemp->getStatus(), [AvatarRenameWorkflow::PLACE_UPLOADED, AvatarRenameWorkflow::PLACE_VALIDATED, AvatarRenameWorkflow::PLACE_ERROR], true)) {
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

        return isset($rename['avatarTempId']) && is_numeric($rename['avatarTempId']);
    }

    private function isSafeAvatarName(string $newName): bool
    {
        return preg_match('/^[A-Za-z0-9_-]+\.png$/', $newName) === 1
            && !str_contains($newName, '/')
            && !str_contains($newName, '\\')
            && !str_contains($newName, '..');
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return list<string>
     */
    private function getMissingRequiredFilters(
        string $category,
        array $filters,
        AvatarRenameFilterMapper $renameFilterMapper,
    ): array {
        return array_values(array_filter(
            $renameFilterMapper->getRequiredFilters($category),
            static function (string $filterId) use ($filters): bool {
                $value = $filters[$filterId] ?? null;

                if (is_array($value)) {
                    $value = $value['name'] ?? null;
                }

                return !is_scalar($value) || trim((string) $value) === '';
            },
        ));
    }
}
