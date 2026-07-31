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
    public function show(string $uuid, ReviewRepository $reviews): JsonResponse
    {
        $review = $this->findReview($reviews, $uuid);

        return $this->json([
            'reviewUuid' => $review->getReviewUuid(),
            'product' => ['id' => $review->getProduct()->getId(), 'name' => $review->getProduct()->getName()],
            'orderReference' => $review->getOrder()->getOrderReference(),
            'status' => $review->getStatus()->value,
            'rating' => $review->getRating(),
            'comment' => $review->getComment(),
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
}
