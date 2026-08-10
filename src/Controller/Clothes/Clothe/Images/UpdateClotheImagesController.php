<?php

namespace App\Controller\Clothes\Clothe\Images;

use App\Application\Clothes\DTO\ClotheImagesUpdateInput;
use App\Application\Clothes\Exception\ClotheNotFoundException;
use App\Application\Clothes\Services\Clothe\ClotheImagesUpdateService;
use App\Notifier\Services\FlashService;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class UpdateClotheImagesController extends AbstractController
{
    #[Route(
        '/clothes/{slug}/images',
        name: 'app_clothes_images_update',
        methods: ['POST'],
    )]
    public function updateImages(
        string $slug,
        Request $request,
        ClotheImagesUpdateService $updateService,
        FlashService $flashService,
        LoggerService $logger,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid(
            'clothe_images_'.$slug,
            (string) $request->request->get('_csrf_token'),
        )) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for clothe images update.', [
                'slug' => $slug,
            ]);

            return $this->redirectToRoute('app_clothes_show', [
                'slug' => $slug,
            ]);
        }

        $colorId = $request->query->getInt('color') ?: null;

        try {
            $uploadedImages = $request->files->all('uploaded_images');
            $updateService->update(new ClotheImagesUpdateInput(
                slug: $slug,
                colorId: $colorId,
                keptImages: $request->request->all('images'),
                uploadedImages: is_array($uploadedImages)
                    ? $uploadedImages
                    : [],
            ));
        } catch (ClotheNotFoundException $exception) {
            throw $this->createNotFoundException(
                $exception->getMessage(),
                $exception,
            );
        } catch (\InvalidArgumentException $exception) {
            $flashService->error($exception->getMessage());
            $logger->warning('Clothe images update rejected.', [
                'slug' => $slug,
                'color_id' => $colorId,
                'error' => $exception->getMessage(),
            ]);

            return $this->redirectToRoute('app_clothes_show', [
                'slug' => $slug,
            ]);
        }

        $flashService->success(
            null === $colorId
                ? 'Images du vetement mises a jour.'
                : 'Images du variant mises a jour.',
        );
        $logger->info('Clothe images updated.', [
            'slug' => $slug,
            'color_id' => $colorId,
        ]);

        return $this->redirectToRoute('app_clothes_show', [
            'slug' => $slug,
        ]);
    }
}
