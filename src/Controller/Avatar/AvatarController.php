<?php

namespace App\Controller\Avatar;

use App\Application\Avatar\Services\AvatarProductGridService;
use App\Application\Avatar\Services\AvatarUploadService;
use App\Service\BreadscrumbsService;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class AvatarController extends AbstractController
{
    #[Route('/avatar', name: 'app_avatar')]
    public function index(
        Request $request,
        BreadscrumbsService $breadscrumbs,
        AvatarProductGridService $avatarProductGridService,
    ): Response {
        $route = $request->attributes->get('_route');

        return $this->render('avatar/index.html.twig', [
            'breadscrumbs' => $breadscrumbs->resolve($route),
            'gridData' => $avatarProductGridService->createProductGridView(
                part: 'body',
                selectedFilters: [],
                id: 'avatar-grid'
            ),
        ]);
    }

    #[Route('/avatar/add', name: 'app_avatar_add', methods: ['GET', 'POST'])]
    public function add(
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
        BreadscrumbsService $breadscrumbs,
        AvatarUploadService $avatarUploadService,
        LoggerService $logger,
    ): Response {
        $route = $request->attributes->get('_route');

        if ($request->isMethod('GET')) {
            return $this->render('avatar/add.html.twig', [
                'breadscrumbs' => $breadscrumbs->resolve($route),
                'csrf_token' => $csrfTokenManager->getToken('avatar_upload')->getValue(),
            ]);
        }

        $token = (string) $request->headers->get('X-CSRF-TOKEN', '');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('avatar_upload', $token))) {
            $logger->warning('Invalid CSRF token for avatar upload.');

            return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        $result = $avatarUploadService->handleChunkUpload($request);
        if ($result->httpStatus >= 400) {
            $logger->warning('Avatar upload failed.', [
                'status' => $result->httpStatus,
                'result' => $result->toArray(),
            ]);
        }

        return $this->json($result->toArray(), $result->httpStatus);
    }
}
