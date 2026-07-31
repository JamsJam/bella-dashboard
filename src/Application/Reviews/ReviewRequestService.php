<?php

namespace App\Application\Reviews;

use App\Entity\Orders\Orders;
use App\Entity\Reviews\Review;
use App\Entity\Users\Customers;
use App\Repository\Reviews\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;

final readonly class ReviewRequestService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReviewRepository $reviewRepository,
        private ClockInterface $clock,
    ) {}

    /** @return list<Review> */
    public function createForOrder(Orders $order): array
    {
        $customer = $order->getCustomer();
        if (!$customer instanceof Customers) {
            throw new \DomainException('La commande ne possède pas de client.');
        }

        $reviews = [];
        $seen = [];
        foreach ($order->getCart()->getItems() as $item) {
            $variant = $item->getVariant();
            if ($variant === null || isset($seen[(int) $variant->getId()])) {
                continue;
            }
            $seen[(int) $variant->getId()] = true;
            $review = $this->reviewRepository->findOneBy([
                'product' => $variant,
                'order' => $order,
                'customer' => $customer,
            ]);
            if (!$review instanceof Review) {
                $review = new Review($variant, $order, $customer, $this->clock->now());
                $this->entityManager->persist($review);
            }
            $reviews[] = $review;
        }
        $this->entityManager->flush();
        return $reviews;
    }
}
