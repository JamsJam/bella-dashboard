<?php

namespace App\Controller\Reviews;

use App\Application\Reviews\ReviewWorkflowService;
use App\Entity\Reviews\Review;
use App\Enum\ReviewStatus;
use App\Repository\Reviews\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reviews')]
final class ReviewApiController extends AbstractController
{
    #[Route('/{uuid}', name: 'api_review_show', requirements: ['uuid' => '[0-9a-fA-F-]{36}'], methods: ['GET'])]
    public function show(string $uuid, Request $request, ReviewRepository $reviews): JsonResponse
    {
        $review = $this->findReview($reviews, $uuid);
        $orderReviews = $reviews->findBy(
            ['order' => $review->getOrder(), 'customer' => $review->getCustomer()],
            ['id' => 'ASC'],
        );
        $currentReview = $this->mapReview($review, $request);

        return $this->json($currentReview + [
            'reviews' => array_map(
                fn (Review $orderReview): array => $this->mapReview($orderReview, $request),
                $orderReviews,
            ),
        ]);
    }

    #[Route('/{uuid}', name: 'api_review_submit', requirements: ['uuid' => '[0-9a-fA-F-]{36}'], methods: ['POST'])]
    public function submit(string $uuid, Request $request, ReviewRepository $reviews, ReviewWorkflowService $workflow): JsonResponse
    {
        $review = $this->findReview($reviews, $uuid);
        if ($review->getStatus() !== ReviewStatus::Requested) {
            return $this->json(['message' => 'Cet avis a déjà été envoyé.'], Response::HTTP_CONFLICT);
        }

        try {
            $payload = $request->toArray();
            $workflow->submit($review, (int) ($payload['rating'] ?? 0), (string) ($payload['comment'] ?? ''));
        } catch (\JsonException|\InvalidArgumentException|\DomainException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(['reviewUuid' => $review->getReviewUuid(), 'status' => $review->getStatus()->value]);
    }

    private function findReview(ReviewRepository $reviews, string $uuid): Review
    {
        $review = $reviews->findOneByUuid($uuid);
        if (!$review instanceof Review) {
            throw $this->createNotFoundException('Avis introuvable.');
        }
        return $review;
    }

    /** @return list<string> */
    private function productImages(Review $review, Request $request): array
    {
        $paths = [];
        $product = $review->getProduct();
        $variants = [$product, ...$product->getClothes()?->getVariants()->toArray() ?? []];

        foreach ($variants as $variant) {
            foreach ($variant->getImages() ?? [] as $path) {
                if (!is_string($path) || trim($path) === '') {
                    continue;
                }

                $path = trim($path);
                if (preg_match('#^https?://#i', $path) !== 1) {
                    $path = str_starts_with($path, '//')
                        ? $request->getScheme().':'.$path
                        : $request->getSchemeAndHttpHost().'/'.ltrim($path, '/');
                }
                $paths[$path] = $path;
            }
        }

        return array_values($paths);
    }

    /**
     * @return array{
     *     reviewUuid: string,
     *     product: array{id: int|null, name: string|null, image: string|null, images: list<string>},
     *     orderReference: string|null,
     *     status: string,
     *     rating: int|null,
     *     comment: string|null
     * }
     */
    private function mapReview(Review $review, Request $request): array
    {
        $images = $this->productImages($review, $request);

        return [
            'reviewUuid' => $review->getReviewUuid(),
            'product' => [
                'id' => $review->getProduct()->getId(),
                'name' => $review->getProduct()->getName(),
                'image' => $images[0] ?? null,
                'images' => $images,
            ],
            'orderReference' => $review->getOrder()->getOrderReference(),
            'status' => $review->getStatus()->value,
            'rating' => $review->getRating(),
            'comment' => $review->getComment(),
        ];
    }
}
