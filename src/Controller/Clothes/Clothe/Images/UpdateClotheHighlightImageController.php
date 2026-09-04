<?php

namespace App\Controller\Clothes\Clothe\Images;

use App\Application\Clothes\DTO\ClotheHighlightImageUpdateInput;
use App\Application\Clothes\Exception\ClotheNotFoundException;
use App\Application\Clothes\Services\Clothe\ClotheHighlightImageUpdateService;
use App\Notifier\Services\FlashService;
use App\Service\LoggerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class UpdateClotheHighlightImageController extends AbstractController
{
    #[Route(
        '/clothes/{slug}/highlight-image/{slot}',
        name: 'app_clothes_highlight_image_update',
        requirements: ['slot' => 'carousel|bestseller'],
        methods: ['POST'],
    )]
    public function updateHighlightImage(
        string $slug,
        string $slot,
        Request $request,
        ClotheHighlightImageUpdateService $updateService,
        FlashService $flashService,
        LoggerService $logger,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid(
            'clothe_highlight_image_'.$slug.'_'.$slot,
            (string) $request->request->get('_csrf_token'),
        )) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning(
                'Invalid CSRF token for clothe highlight image update.',
                ['slug' => $slug, 'slot' => $slot],
            );

            return $this->redirectToRoute('app_clothes_show', [
                'slug' => $slug,
            ]);
        }

        $uploadedImage = $request->files->get('uploaded_image');

        try {
            $updateService->update(new ClotheHighlightImageUpdateInput(
                slug: $slug,
                slot: $slot,
                selectedImage: (string) $request->request->get(
                    'selected_image',
                    '',
                ),
                uploadedImage: $uploadedImage instanceof UploadedFile
                    ? $uploadedImage
                    : null,
            ));
        } catch (ClotheNotFoundException $exception) {
            throw $this->createNotFoundException(
                $exception->getMessage(),
                $exception,
            );
        } catch (\InvalidArgumentException $exception) {
            $flashService->error($exception->getMessage());
            $logger->warning('Invalid highlight image selected.', [
                'slug' => $slug,
                'slot' => $slot,
                'error' => $exception->getMessage(),
            ]);

            return $this->redirectToRoute('app_clothes_show', [
                'slug' => $slug,
            ]);
        }

        $flashService->success(
            'carousel' === $slot
                ? 'Image de mise en avant mise a jour.'
                : 'Image bestseller mise a jour.',
        );
        $logger->info('Clothe highlight image updated.', [
            'slug' => $slug,
            'slot' => $slot,
        ]);

        return $this->redirectToRoute('app_clothes_show', [
            'slug' => $slug,
        ]);
    }
}
