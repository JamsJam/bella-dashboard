<?php

namespace App\Controller\Clothes\Clothe\Featured;

use App\Entity\Clothes\ClothesVariant;
use App\Notifier\Services\FlashService;
use App\Repository\Clothes\ClothesRepository;
use App\Service\LoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class UpdateFeaturedClothesController extends AbstractController
{
    #[Route('/clothes/featured', name: 'app_clothes_featured_update', methods: ['POST'])]
    public function updateFeatured(
        Request $request,
        ClothesRepository $clothesRepository,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        FlashService $flashService,
        LoggerService $logger,
    ): Response {
        $token = new CsrfToken('clothe_featured', $this->readCsrfToken($request));
        if (!$csrfTokenManager->isTokenValid($token)) {
            $logger->warning('Invalid CSRF token for featured clothes update.');

            return $this->json(['success' => false, 'error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $mode = $this->readBestsellerMode($request);
        $slugs = 'remove' === $mode
            ? $this->readBestsellerSlugs($request)
            : $this->extractVariantSlugs($this->findVariantsByIds($this->readBestsellerIds($request), $entityManager));

        if ('replace' === $mode) {
            $keptSlugMap = array_flip($slugs);

            foreach ($clothesRepository->findFeaturedVariants() as $variant) {
                if ($variant instanceof ClothesVariant && !isset($keptSlugMap[(string) $variant->getSlug()])) {
                    $variant->setIsInCarousel(false);
                }
            }
        }

        foreach ($clothesRepository->findVariantsBySlugs($slugs) as $variant) {
            if ($variant instanceof ClothesVariant) {
                $variant->setIsInCarousel('remove' !== $mode);
            }
        }

        $entityManager->flush();
        $flashService->success('Mise en avant des vetements mise a jour.');
        $logger->info('Featured clothes updated.', [
            'mode' => $mode,
            'slugs_count' => count($slugs),
        ]);

        if ($this->wantsTurboStream($request) && !$request->isXmlHttpRequest()) {
            return new Response(
                '<turbo-stream action="update" target="modal-root"><template></template></turbo-stream>',
                Response::HTTP_OK,
                ['Content-Type' => 'text/vnd.turbo-stream.html'],
            );
        }

        if (!$this->wantsJson($request)) {
            return $this->redirectToRoute('app_clothes');
        }

        return $this->json([
            'success' => true,
            'checked' => 'remove' !== $mode,
        ]);
    }

    private function readCsrfToken(Request $request): string
    {
        return (string) (
            $request->headers->get('X-CSRF-TOKEN')
            ?: $request->request->get('_csrf_token', '')
        );
    }

    private function readBestsellerMode(Request $request): string
    {
        $payload = $this->readJsonPayload($request);
        $mode = (string) ($request->request->get('mode') ?: ($payload['mode'] ?? 'add'));

        return in_array($mode, ['add', 'replace', 'remove'], true) ? $mode : 'add';
    }

    private function readBestsellerSlugs(Request $request): array
    {
        $payload = $this->readJsonPayload($request);
        $slugs = $request->request->all('slugs') ?: ($payload['slugs'] ?? []);

        if ((!is_array($slugs) || [] === $slugs) && isset($payload['slug'])) {
            $slugs = [$payload['slug']];
        }

        if (!is_array($slugs)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $slug): string => trim((string) $slug),
            $slugs,
        ))));
    }

    private function extractVariantSlugs(array $variants): array
    {
        return array_values(array_filter(array_map(
            static fn (ClothesVariant $variant): ?string => $variant->getSlug(),
            $variants,
        )));
    }

    private function findVariantsByIds(array $ids, EntityManagerInterface $entityManager): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
        if ([] === $ids) {
            return [];
        }

        return $entityManager->getRepository(ClothesVariant::class)->findBy(['id' => $ids]);
    }

    private function readBestsellerIds(Request $request): array
    {
        $payload = $this->readJsonPayload($request);
        $ids = $request->request->all('ids') ?: ($payload['ids'] ?? []);

        if (!is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $id): int => (int) $id,
            $ids,
        ), static fn (int $id): bool => $id > 0)));
    }

    private function wantsTurboStream(Request $request): bool
    {
        return str_contains((string) $request->headers->get('Accept'), 'text/vnd.turbo-stream.html');
    }

    private function wantsJson(Request $request): bool
    {
        return $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept'), 'application/json');
    }

    private function readJsonPayload(Request $request): array
    {
        if (!str_contains((string) $request->headers->get('Content-Type'), 'application/json')) {
            return [];
        }

        $payload = json_decode($request->getContent(), true);

        return is_array($payload) ? $payload : [];
    }
}
