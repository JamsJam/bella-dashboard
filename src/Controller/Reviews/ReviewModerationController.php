<?php

namespace App\Controller\Reviews;

use App\Application\Reviews\ReviewWorkflowService;
use App\Entity\Reviews\Review;
use App\Repository\Reviews\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ReviewModerationController extends AbstractController
{
    #[Route('/reviews/{id}/moderate', name: 'app_review_moderate', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function moderate(int $id, Request $request, ReviewRepository $reviews, ReviewWorkflowService $workflow): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $review = $reviews->find($id);
        if (!$review instanceof Review) {
            throw $this->createNotFoundException('Avis introuvable.');
        }
        $decision = (string) $request->request->get('decision');
        if (!in_array($decision, ['accepted', 'rejected'], true)) {
            $this->addFlash('error', 'La décision doit être acceptée ou refusée.');

            return $this->redirectToRoute('app_reviews_show', ['id' => $id]);
        }
        if (!$this->isCsrfTokenValid('review_action_' . $id, (string) $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        try {
            $workflow->moderate($review, 'accepted' === $decision);
        } catch (\InvalidArgumentException | \DomainException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_reviews_show', ['id' => $id]);
        }
        $this->addFlash('success', 'accepted' === $decision ? 'L’avis a été accepté.' : 'L’avis a été refusé.');

        return $this->redirectToRoute('app_reviews_show', ['id' => $id]);
    }

    #[Route('/reviews/{id}/reply', name: 'app_review_reply', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function reply(int $id, Request $request, ReviewRepository $reviews, ReviewWorkflowService $workflow): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $review = $reviews->find($id);
        if (!$review instanceof Review) {
            throw $this->createNotFoundException('Avis introuvable.');
        }
        if (!$this->isCsrfTokenValid('review_action_' . $id, (string) $request->request->get('_csrf_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        try {
            $workflow->reply($review, $request->request->getString('reply'));
        } catch (\InvalidArgumentException | \DomainException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_reviews_show', ['id' => $id]);
        }

        $this->addFlash('success', 'La réponse a été enregistrée sans modifier le statut.');

        return $this->redirectToRoute('app_reviews_show', ['id' => $id]);
    }
}
