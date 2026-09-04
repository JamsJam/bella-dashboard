<?php

namespace App\Controller\Clothes\Clothe\Edit;

use App\Application\Clothes\Services\Clothe\ClotheRenameService;
use App\Application\Clothes\Services\Clothe\ClotheService;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Collections\Collections;
use App\Notifier\Services\FlashService;
use App\Service\LoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class UpdateClotheController extends AbstractController
{
    #[Route('/clothes/{slug}/edit', name: 'app_clothes_update', methods: ['POST'])]
    public function update(
        string $slug,
        Request $request,
        ClotheService $clotheService,
        ClotheRenameService $clotheRenameService,
        EntityManagerInterface $entityManager,
        FlashService $flashService,
        LoggerService $logger,
    ): RedirectResponse {
        if (!$this->isCsrfTokenValid('clothe_edit_' . $slug, (string) $request->getPayload()->get('_csrf_token'))) {
            $flashService->error('Token CSRF invalide.');
            $logger->warning('Invalid CSRF token for clothe edit.', ['slug' => $slug]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        $variants = $clotheService->getClotheVariantsBySlug($slug);
        if ([] === $variants) {
            $logger->warning('Clothe not found for update.', ['slug' => $slug]);
            throw $this->createNotFoundException('Clothe not found.');
        }

        $collection = $entityManager->getRepository(Collections::class)->find($request->request->getInt('collection'));
        $price = $request->request->getInt('price');
        $name = (string) $request->request->get('name', '');

        if (
            !$collection instanceof Collections
            || $price <= 0
        ) {
            $flashService->error('Collection ou prix invalide.');
            $logger->warning('Invalid data for clothe update.', ['slug' => $slug]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        try {
            $mainClothe = $this->resolveMainClothe($variants);
            $newSlug = $clotheRenameService->renameClothe($mainClothe, $slug, $name);
            $now = new \DateTimeImmutable();
            $mainClothe
                ->setCollection($collection)
                ->setPrice($price)
                ->setEditedAt($now);
            foreach ($variants as $variant) {
                if ($variant instanceof ClothesVariant) {
                    $variant
                        ->setSku($this->createVariantSku($mainClothe, $variant->getColor(), $variant->getSize()))
                        ->setEditedAt($now);
                }
            }
        } catch (\InvalidArgumentException $exception) {
            $flashService->error($exception->getMessage());
            $logger->warning('Clothe rename rejected.', [
                'slug' => $slug,
                'error' => $exception->getMessage(),
            ]);

            return $this->redirectToRoute('app_clothes_show', ['slug' => $slug]);
        }

        $entityManager->flush();
        $flashService->success('Informations du vetement mises a jour.');
        $logger->info('Clothe updated.', [
            'old_slug' => $slug,
            'new_slug' => $newSlug,
            'collection_id' => $collection->getId(),
        ]);

        return $this->redirectToRoute('app_clothes_show', ['slug' => $newSlug]);
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

    private function createVariantSku(Clothes $clothe, ?Clothescolor $color, ?Clothessize $size): string
    {
        $slugger = new AsciiSlugger();

        return strtoupper(sprintf(
            '%s-%s-%s',
            (string) $slugger->slug((string) $clothe->getName()),
            (string) $slugger->slug((string) $color?->getName()),
            (string) $slugger->slug((string) $size?->getName()),
        ));
    }
}
