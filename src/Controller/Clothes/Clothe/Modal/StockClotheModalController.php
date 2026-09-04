<?php

namespace App\Controller\Clothes\Clothe\Modal;

use App\Application\Clothes\Services\Clothe\ClotheService;
use App\Entity\Clothes\Clothescolor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class StockClotheModalController extends AbstractController
{
    #[Route('/clothes/{slug}/stock/modal', name: 'app_clothes_stock_modal', methods: ['GET'])]
    public function stockModal(
        string $slug,
        ClotheService $clotheService,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        $variants = $clotheService->getClotheVariantsBySlug($slug);
        if ([] === $variants) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        return $this->render('clothes/_stock_modal.html.twig', [
            'variants' => $variants,
            'colors' => $entityManager->getRepository(Clothescolor::class)->findBy([], ['name' => 'ASC']),
            'availableSizes' => ClotheService::AVAILABLE_SIZES,
            'action' => $this->generateUrl('app_clothes_stock_update', ['slug' => $slug]),
            'csrfToken' => $csrfTokenManager->getToken('clothe_stock_' . $slug)->getValue(),
        ]);
    }
}
