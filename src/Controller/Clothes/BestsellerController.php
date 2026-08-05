<?php

namespace App\Controller\Clothes;

use App\Application\Clothes\Model\BestsellerUpdateResult;
use App\Application\Clothes\Services\ClotheBestsellerService;
use App\Entity\Clothes\Clothes;
use App\Notifier\Services\FlashService;
use App\Service\LoggerService;
use App\UI\ProductGrid\ProductGridItemModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class BestsellerController extends AbstractController
{
    #[Route('/clothes/bestsellers/list', name: 'app_clothes_bestsellers', methods: ['GET'])]
    public function list(ClotheBestsellerService $bestsellerService): JsonResponse
    {
        return $this->json([
            'success' => true,
            'cacheKey' => ClotheBestsellerService::CACHE_KEY,
            'maxItems' => $bestsellerService->getMaxItems(),
            'items' => array_map(
                fn (Clothes $clothe): array => $this->mapClothe($clothe)->toArray(),
                $bestsellerService->createCacheIfMissing(),
            ),
        ]);
    }

    #[Route('/clothes/bestsellers/modal', name: 'app_clothes_bestsellers_modal', methods: ['GET'])]
    public function modal(
        ClotheBestsellerService $bestsellerService,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $html = $this->renderView('clothes/_bestseller_modal.html.twig', [
            'action' => $this->generateUrl('app_clothes_bestsellers_update'),
            'csrfToken' => $csrfTokenManager->getToken('clothe_bestseller')->getValue(),
            'bestsellers' => $bestsellerService->createCacheIfMissing(),
            'maxItems' => $bestsellerService->getMaxItems(),
        ]);

        return new Response(
            sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
            Response::HTTP_OK,
            ['Content-Type' => 'text/vnd.turbo-stream.html'],
        );
    }

    #[Route('/clothes/bestsellers', name: 'app_clothes_bestsellers_update', methods: ['POST'])]
    public function update(
        Request $request,
        ClotheBestsellerService $bestsellerService,
        CsrfTokenManagerInterface $csrfTokenManager,
        FlashService $flashService,
        LoggerService $logger,
    ): Response {
        $token = new CsrfToken('clothe_bestseller', $this->readCsrfToken($request));
        if (!$csrfTokenManager->isTokenValid($token)) {
            $logger->warning('Invalid CSRF token for bestseller update.');

            return $this->json(['success' => false, 'error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $ids = $this->readBestsellerIds($request);
        $mode = $this->readBestsellerMode($request);
        $pruneOverflow = $this->readBestsellerPruneOverflow($request);

        $result = match ($mode) {
            'replace' => $bestsellerService->replaceByIds($ids, $pruneOverflow),
            'remove' => $bestsellerService->removeBySlugs($this->readBestsellerSlugs($request)),
            default => $bestsellerService->addByIds($ids, $pruneOverflow),
        };

        if ($result->requiresPruning) {
            $logger->warning('Bestseller update requires pruning.', [
                'mode' => $mode,
                'max_items' => $result->maxItems,
                'overflow_count' => count($result->overflow),
            ]);

            if ($this->wantsTurboStream($request)) {
                $html = $this->renderView('clothes/_bestseller_modal.html.twig', [
                    'action' => $this->generateUrl('app_clothes_bestsellers_update'),
                    'csrfToken' => $csrfTokenManager->getToken('clothe_bestseller')->getValue(),
                    'bestsellers' => array_merge($result->bestsellers, $result->overflow),
                    'maxItems' => $result->maxItems,
                    'errorMessage' => $result->message.' Decoche les elements a supprimer avant d enregistrer.',
                ]);

                return new Response(
                    sprintf('<turbo-stream action="update" target="modal-root"><template>%s</template></turbo-stream>', $html),
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    ['Content-Type' => 'text/vnd.turbo-stream.html'],
                );
            }

            return $this->json($this->mapBestsellerResult($result), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($result->success) {
            $flashService->success($result->message);
            $logger->info('Bestseller list updated.', [
                'mode' => $mode,
                'items_count' => count($result->bestsellers),
            ]);
        }

        if ($this->wantsTurboStream($request) && !$request->isXmlHttpRequest()) {
            return new Response(
                '<turbo-stream action="update" target="modal-root"><template></template></turbo-stream>',
                Response::HTTP_OK,
                ['Content-Type' => 'text/vnd.turbo-stream.html'],
            );
        }

        if ($this->wantsJson($request)) {
            return $this->json($this->mapBestsellerResult($result));
        }

        return $this->redirectToRoute('app_clothes');
    }

    private function mapClothe(Clothes $clothe): ProductGridItemModel
    {
        $images = $clothe->getImages() ?? [];

        return new ProductGridItemModel(
            id: (string) $clothe->getId(),
            name: (string) $clothe->getName(),
            imageUrl: (string) ($images[0] ?? $clothe->getCollection()?->getImage() ?? ''),
            imageUrls: array_values(array_filter($images)),
            slug: (string) $clothe->getSlug(),
            isOnline: $this->isEffectivelyOnline($clothe),
            attributes: [
                'collection' => $clothe->getCollection()?->getName(),
                'category' => $clothe->getCollection()?->getCategory()?->getName(),
                'color' => $clothe->getColor()?->getName(),
            ],
        );
    }

    private function isEffectivelyOnline(Clothes $clothe): bool
    {
        $collection = $clothe->getCollection();
        $category = $collection?->getCategory();

        return (bool) $category?->isOnline()
            && (bool) $collection?->isOnline()
            && $clothe->hasOnlineVariant();
    }

    /**
     * @return list<int>
     */
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

    private function readBestsellerMode(Request $request): string
    {
        $payload = $this->readJsonPayload($request);
        $mode = (string) ($request->request->get('mode') ?: ($payload['mode'] ?? 'add'));

        return in_array($mode, ['add', 'replace', 'remove'], true) ? $mode : 'add';
    }

    /**
     * @return list<string>
     */
    private function readBestsellerSlugs(Request $request): array
    {
        $payload = $this->readJsonPayload($request);
        $slugs = $request->request->all('slugs') ?: ($payload['slugs'] ?? []);

        if ((!is_array($slugs) || $slugs === []) && isset($payload['slug'])) {
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

    private function readBestsellerPruneOverflow(Request $request): bool
    {
        $payload = $this->readJsonPayload($request);

        return $request->request->getBoolean('prune_overflow') || (bool) ($payload['pruneOverflow'] ?? false);
    }

    private function readCsrfToken(Request $request): string
    {
        return (string) (
            $request->headers->get('X-CSRF-TOKEN')
            ?: $request->request->get('_csrf_token', '')
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonPayload(Request $request): array
    {
        if (!str_contains((string) $request->headers->get('Content-Type'), 'application/json')) {
            return [];
        }

        $payload = json_decode($request->getContent(), true);

        return is_array($payload) ? $payload : [];
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

    /**
     * @return array<string, mixed>
     */
    private function mapBestsellerResult(BestsellerUpdateResult $result): array
    {
        return [
            'success' => $result->success,
            'requiresPruning' => $result->requiresPruning,
            'maxItems' => $result->maxItems,
            'message' => $result->message,
            'items' => array_map(
                fn (Clothes $clothe): array => $this->mapClothe($clothe)->toArray(),
                $result->bestsellers,
            ),
            'overflow' => array_map(
                fn (Clothes $clothe): array => $this->mapClothe($clothe)->toArray(),
                $result->overflow,
            ),
        ];
    }
}
