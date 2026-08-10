<?php

namespace App\Controller\Clothes\Clothe\Modal;

use App\Application\Clothes\Services\Clothe\ClotheService;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Collections\Collections;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EditClotheModalController extends AbstractController
{
    #[Route('/clothes/{slug}/edit/modal', name: 'app_clothes_edit_modal', methods: ['GET'])]
    public function editModal(
        string $slug,
        ClotheService $clotheService,
        EntityManagerInterface $entityManager,
    ): Response {
        $variants = $clotheService->getClotheVariantsBySlug($slug);
        if ([] === $variants) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        $mainClothe = $this->resolveMainClothe($variants);

        return $this->render('clothes/_edit_modal.html.twig', [
            'clothe' => $mainClothe,
            'collections' => $entityManager->getRepository(Collections::class)->findBy([], ['name' => 'ASC']),
            'action' => $this->generateUrl('app_clothes_update', ['slug' => $slug]),
            'slug' => $slug,
        ]);
    }

    private function resolveMainClothe(array $variants): Clothes
    {
        $firstVariant = $variants[0] ?? null;
        $clothe = $firstVariant instanceof ClothesVariant ? $firstVariant->getClothes() : null;

        if (!$clothe instanceof Clothes) {
            throw $this->createNotFoundException('Clothe not found.');
        }

        return $clothe;
    }
}
