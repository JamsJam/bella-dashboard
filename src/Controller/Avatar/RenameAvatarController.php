<?php

namespace App\Controller\Avatar;

use App\Application\Avatar\Exception\AvatarInputValidationException;
use App\Application\Avatar\Mapper\AvatarFilterMapper;
use App\Application\Avatar\Mapper\AvatarTempViewMapper;
use App\Application\Avatar\Resolver\AvatarTemporaryFileResolver;
use App\Application\Avatar\Services\AvatarRenameBatchInputService;
use App\Application\Avatar\Services\AvatarRenameValidationInputService;
use App\Application\Avatar\Services\AvatarValidatedFilterValueService;
use App\Application\Avatar\Workflow\AvatarRenameGuardContextStore;
use App\Application\Avatar\Workflow\AvatarRenameValidationContext;
use App\Application\Avatar\Workflow\AvatarRenameWorkflow;
use App\Application\Avatar\Workflow\Guard\AvatarOverwriteAuthorizationGuard;
use App\Entity\AvatarTemp;
use App\Message\Avatar\RenameAvatarMessage;
use App\Notifier\Services\FlashService;
use App\Service\BreadscrumbsService;
use App\Service\FileManagerService;
use App\Service\LoggerService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
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
        AvatarTempViewMapper $avatarTempViewMapper,
    ): Response {
        $avatarTemps = $entityManager->getRepository(AvatarTemp::class)->findBy(
            ['status' => [AvatarRenameWorkflow::PLACE_UPLOADED, AvatarRenameWorkflow::PLACE_VALIDATED, AvatarRenameWorkflow::PLACE_ERROR]],
            ['createdAt' => 'ASC'],
        );

        return $this->render('avatar/rename.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve((string) $request->attributes->get('_route')),
            'avatars' => array_map($avatarTempViewMapper->map(...), $avatarTemps),
            'partLabels' => array_filter(
                $avatarFilterMapper->getPartLabels(),
                static fn (string $part): bool => 'accessory' !== $part,
                ARRAY_FILTER_USE_KEY,
            ),
            'filter_url' => $this->generateUrl('app_search_avatar_filters'),
        ]);
    }

    #[Route('/{id}/validate', name: 'app_avatar_rename_validate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function validateName(
        AvatarTemp $avatarTemp,
        Request $request,
        AvatarRenameValidationInputService $validationInputService,
        EntityManagerInterface $entityManager,
        #[Autowire(service: 'state_machine.avatar_rename')]
        WorkflowInterface $workflow,
        LoggerService $logger,
        AvatarRenameGuardContextStore $guardContextStore,
        FlashService $flashService,
        AvatarValidatedFilterValueService $validatedFilterValueService,
    ): Response {
        if (!$this->isCsrfTokenValid('avatar_rename_validate_' . $avatarTemp->getId(), (string) $request->request->get('_csrf_token', ''))) {
            $flashService->error('Token CSRF invalide.');

            return $this->json(['error' => 'Token CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $input = $validationInputService->prepare(
                name: (string) $request->request->get('name', ''),
                category: (string) $request->request->get('category', ''),
                filtersJson: (string) $request->request->get('filters', '{}'),
                authorization: $request->request->get('authorization', false),
            );
        } catch (AvatarInputValidationException $exception) {
            $logger->warning('Invalid avatar rename check payload.', [
                'name' => (string) $request->request->get('name', ''),
                'category' => (string) $request->request->get('category', ''),
                'violations' => $exception->errors(),
            ]);
            $message = $exception->firstError();
            $flashService->error($message);

            return $this->json([
                'error' => $message,
                'violations' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var array<string, mixed> $filters */
        $filters = $input->filters;
        $context = new AvatarRenameValidationContext($input->name, $input->category, $filters, $input->authorization);
        $guardContextStore->setValidation($avatarTemp, $context);

        try {
            $workflow->apply($avatarTemp, AvatarRenameWorkflow::TRANSITION_VALIDATE, ['validation' => $context]);
        } catch (NotEnabledTransitionException $exception) {
            foreach ($exception->getTransitionBlockerList() as $blocker) {
                if (AvatarOverwriteAuthorizationGuard::BLOCKER_TARGET_ALREADY_EXISTS === $blocker->getCode()) {
                    $html = $this->renderView('avatar/_rename_confirmation_modal.html.twig', [
                        'avatar' => $avatarTemp,
                        'name' => $input->name,
                        'category' => $input->category,
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

        $validatedFilterValueService->persistNewValues($input->category, $filters);
        $avatarTemp->setFinalName($input->name);
        $entityManager->flush();

        return $this->json(['success' => true, 'status' => $avatarTemp->getStatus(), 'name' => $input->name]);
    }

    #[Route('', name: 'app_avatar_rename_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        MessageBusInterface $messageBus,
        EntityManagerInterface $entityManager,
        FlashService $flashService,
        LoggerService $logger,
        AvatarRenameBatchInputService $batchInputService,
        #[Autowire(service: 'state_machine.avatar_rename')]
        WorkflowInterface $workflow,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('avatar_rename', (string) $request->request->get('_csrf_token', ''))) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for avatar rename submit.');

            return $this->redirectToRoute('app_avatar_rename');
        }

        try {
            $input = $batchInputService->prepare(
                (string) $request->request->get('renames', '[]'),
            );
        } catch (AvatarInputValidationException $exception) {
            $flashService->error($exception->firstError());
            $logger->warning('Invalid avatar rename payload.', [
                'violations' => $exception->errors(),
            ]);

            return $this->redirectToRoute('app_avatar_rename');
        }

        $dispatched = 0;
        foreach ($input->avatarTempIds() as $avatarTempId) {
            $started = $entityManager->wrapInTransaction(function (EntityManagerInterface $manager) use ($avatarTempId, $workflow): bool {
                $avatarTemp = $manager->find(AvatarTemp::class, $avatarTempId, LockMode::PESSIMISTIC_WRITE);
                if (!$avatarTemp instanceof AvatarTemp || AvatarRenameWorkflow::PLACE_VALIDATED !== $avatarTemp->getStatus()) {
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
            ++$dispatched;
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
        if (!$this->isCsrfTokenValid('avatar_rename_retry_' . $avatarTemp->getId(), (string) $request->request->get('_csrf_token', ''))) {
            return $this->json(['error' => 'Token CSRF invalide.'], Response::HTTP_FORBIDDEN);
        }

        if (!$workflow->can($avatarTemp, AvatarRenameWorkflow::TRANSITION_RETRY)) {
            return $this->json(['error' => 'Cette image ne peut pas être relancée.'], Response::HTTP_CONFLICT);
        }

        $workflow->apply($avatarTemp, AvatarRenameWorkflow::TRANSITION_RETRY);
        $avatarTemp->setFinalName(null);
        $entityManager->flush();

        if ('json' === $request->getPreferredFormat()) {
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
        if (!$this->isCsrfTokenValid('avatar_rename_cancel_validation_' . $avatarTemp->getId(), (string) $request->request->get('_csrf_token', ''))) {
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
        AvatarTemporaryFileResolver $temporaryFileResolver,
        FileManagerService $fileManager,
    ): Response {
        $csrfToken = (string) ($request->headers->get('X-CSRF-TOKEN') ?: $request->request->get('_csrf_token', ''));
        if (!$this->isCsrfTokenValid('avatar_temp_delete', $csrfToken)) {
            $logger->warning('Invalid CSRF token for avatar temp deletion.', [
                'avatar_temp_id' => $avatarTemp->getId(),
            ]);

            return $this->json(['success' => false, 'error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        if (!in_array($avatarTemp->getStatus(), [AvatarRenameWorkflow::PLACE_UPLOADED, AvatarRenameWorkflow::PLACE_ERROR], true)) {
            $logger->warning('Avatar temp deletion rejected for current status.', [
                'avatar_temp_id' => $avatarTemp->getId(),
                'status' => $avatarTemp->getStatus(),
            ]);

            return $this->json(['success' => false, 'error' => 'Cette image ne peut pas etre supprimee.'], Response::HTTP_CONFLICT);
        }

        $tempPath = $temporaryFileResolver->resolve($avatarTemp);
        if (null !== $tempPath) {
            $fileManager->remove($tempPath);
            $fileManager->removeEmptyDirectory(dirname($tempPath));
        }

        $entityManager->remove($avatarTemp);
        $entityManager->flush();
        $logger->info('Avatar temp deleted.', [
            'avatar_temp_id' => $avatarTemp->getId(),
        ]);

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true]);
        }

        return $this->redirectToRoute('app_avatar_rename');
    }

    #[Route('/preview/{id}', name: 'app_avatar_rename_preview', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function preview(
        AvatarTemp $avatarTemp,
        AvatarTemporaryFileResolver $temporaryFileResolver,
    ): Response {
        if (!in_array($avatarTemp->getStatus(), [AvatarRenameWorkflow::PLACE_UPLOADED, AvatarRenameWorkflow::PLACE_VALIDATED, AvatarRenameWorkflow::PLACE_ERROR], true)) {
            throw $this->createNotFoundException('Avatar temporary image not found.');
        }

        $path = $temporaryFileResolver->resolve($avatarTemp);
        if (null === $path) {
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
}
