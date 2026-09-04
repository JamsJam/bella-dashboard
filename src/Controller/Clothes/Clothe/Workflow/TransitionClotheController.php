<?php

namespace App\Controller\Clothes\Clothe\Workflow;

use App\Application\Clothes\Services\Clothe\ClotheWorkflowService;
use App\Application\Config\Service\SiteTimezone;
use App\Entity\Clothes\ClothesVariant;
use App\Notifier\Services\FlashService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class TransitionClotheController extends AbstractController
{
    #[Route('/clothes/variant/{id}/workflow/{transition}', name: 'app_clothes_workflow_transition', requirements: ['id' => '\d+'], methods: ['POST'], priority: 40)]
    public function transition(
        ClothesVariant $variant,
        string $transition,
        Request $request,
        ClotheWorkflowService $workflowService,
        FlashService $flashService,
        SiteTimezone $siteTimezone,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('clothe_variant_workflow_' . $variant->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        try {
            $scheduledAt = null;
            if ('programmer_publication' === $transition) {
                $value = trim((string) $request->request->get('scheduledAt'));
                $scheduledAt = '' === $value ? null : $siteTimezone->localInputToUtc($value);
            }
            $workflowService->apply($variant, $transition, $scheduledAt);
            $flashService->success('Nouvel état : ' . $variant->getPublicationStatus()->label() . '.');
        } catch (\Throwable $exception) {
            $flashService->error($exception->getMessage());
        }

        return $this->redirectToRoute('app_clothes_show', ['slug' => $variant->getSlug()]);
    }
}
