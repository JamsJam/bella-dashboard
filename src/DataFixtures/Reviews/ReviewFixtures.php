<?php

namespace App\DataFixtures\Reviews;

use App\DataFixtures\AbstractBaseFixtures;
use App\DataFixtures\FixtureReferences;
use App\DataFixtures\Orders\OrderFixtures;
use App\Entity\Orders\Orders;
use App\Entity\Reviews\Review;
use App\Enum\OrderStatus;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class ReviewFixtures extends AbstractBaseFixtures implements DependentFixtureInterface, FixtureGroupInterface
{
    private const ORDER_COUNT = 500;

    private const COMMENTS = [
        'Très belle qualité, la coupe est parfaite et correspond bien aux photos.',
        'Produit confortable et agréable à porter. Je suis très satisfaite de mon achat.',
        'La couleur est encore plus jolie en vrai et la taille me convient parfaitement.',
        'Commande reçue rapidement, le vêtement est bien fini et de bonne qualité.',
        'J’aime beaucoup ce modèle, il est élégant et facile à assortir.',
        'Le tissu est doux et le rendu est vraiment réussi. Je recommande ce produit.',
        'La taille ne correspondait pas totalement à mes attentes, mais le produit reste joli.',
        'Belle pièce dans l’ensemble, avec une coupe moderne et confortable.',
        'Le produit est conforme à la description et soigneusement emballé.',
        'Très contente de cet achat, je le porte régulièrement depuis sa réception.',
    ];

    private const REPLIES = [
        'Merci beaucoup pour votre retour et votre confiance.',
        'Merci d’avoir pris le temps de partager votre expérience.',
        'Nous sommes ravis que ce modèle vous plaise.',
        'Merci pour votre remarque, elle nous aide à améliorer nos produits.',
        'Nous prenons bien en compte votre retour concernant la taille.',
    ];

    public function load(ObjectManager $manager): void
    {
        $reviewIndex = 0;
        $deliveredOrderIndex = 0;

        for ($orderIndex = 0; $orderIndex < self::ORDER_COUNT; ++$orderIndex) {
            /** @var Orders $order */
            $order = $this->getReference(FixtureReferences::ORDERS.$orderIndex, Orders::class);

            if ($order->getOrderStatus() !== OrderStatus::Delivered || !$order->isPaid()) {
                continue;
            }

            // Seules quatre commandes livrées sur cinq reçoivent des avis.
            if ($deliveredOrderIndex++ % 5 === 0) {
                continue;
            }

            $customer = $order->getCustomer();
            $deliveredAt = $order->getDeliveredAt();
            if ($customer === null || $deliveredAt === null) {
                continue;
            }

            $seenVariants = [];
            foreach ($order->getCart()->getItems() as $item) {
                $variant = $item->getVariant();
                $variantId = $variant?->getId();
                if ($variant === null || $variantId === null || isset($seenVariants[$variantId])) {
                    continue;
                }
                $seenVariants[$variantId] = true;

                $requestedAt = $deliveredAt->modify('+2 hours');
                $submittedAt = $requestedAt->modify('+'.(1 + ($reviewIndex % 18)).' hours');
                $review = new Review($variant, $order, $customer, $requestedAt);
                $review->submit(
                    1 + (($reviewIndex * 7) % 5),
                    self::COMMENTS[$reviewIndex % count(self::COMMENTS)],
                    $submittedAt,
                );

                // Répartition stable : pending, accepted, rejected.
                $moderation = $reviewIndex % 3;
                if ($moderation !== 0) {
                    // La présence d'une réponse est indépendante de la décision de modération.
                    $reply = $reviewIndex % 2 === 0
                        ? self::REPLIES[$reviewIndex % count(self::REPLIES)]
                        : null;
                    $moderatedAt = $submittedAt->modify('+'.(2 + ($reviewIndex % 12)).' hours');

                    if ($moderation === 1) {
                        $review->accept($reply, $moderatedAt);
                    } else {
                        $review->reject($reply, $moderatedAt);
                    }
                }

                $manager->persist($review);
                ++$reviewIndex;
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [OrderFixtures::class];
    }

    public static function getGroups(): array
    {
        return ['commerce-test'];
    }
}
