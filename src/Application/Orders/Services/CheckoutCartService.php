<?php

namespace App\Application\Orders\Services;

use App\ApiResource\Orders\CheckoutCartInput;
use App\ApiResource\Orders\CheckoutShippingInfoInput;
use App\Application\Config\Dto\ShippingFeeDto;
use App\Application\Config\Provider\OrdersConfigProvider;
use App\Application\Orders\Exception\InsufficientVariantStockException;
use App\Application\Orders\Exception\InvalidCheckoutRequestException;
use App\Application\Orders\Exception\InvalidShippingDestinationException;
use App\Application\Orders\Workflow\OrderWorkflow;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Orders\Cart;
use App\Entity\Orders\CartItem;
use App\Entity\Orders\Orders;
use App\Entity\Users\Customers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Workflow\WorkflowInterface;

final readonly class CheckoutCartService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrdersConfigProvider $ordersConfigProvider,
        private OrderReferenceGenerator $orderReferenceGenerator,
        #[Target(OrderWorkflow::NAME)]
        private WorkflowInterface $orderWorkflow,
    ) {
    }

    public function createPendingOrder(CheckoutCartInput $input, Customers $customer): Orders
    {
        $quantitiesByVariant = $this->aggregateItems($input->items);
        $shippingFee = $this->resolveShippingFee($input->shippingDestination);
        $shippingInfo = $this->createShippingInfo($input->shippingInfo, $shippingFee->destination);
        $vatRate = $this->ordersConfigProvider->get()->vat;

        return $this->entityManager->wrapInTransaction(function () use ($input, $customer, $quantitiesByVariant, $shippingFee, $shippingInfo, $vatRate): Orders {
            $cart = (new Cart())
                ->setCustomer($customer)
                ->setCurrency($input->currency);

            foreach ($quantitiesByVariant as $variantId => $quantity) {
                $item = $this->createAndReserveItem($variantId, $quantity);
                $cart->addItem($item);
            }

            $cart->recalculateTotals();
            $this->entityManager->persist($cart);
            $this->entityManager->flush();

            $order = (new Orders())
                ->setCart($cart)
                ->setCustomer($customer)
                ->setSubtotal($cart->getSubtotal())
                ->setTotal($cart->getTotal() + $shippingFee->priceCents)
                ->setStatus(Orders::STATUS_PENDING_PAYMENT)
                ->setOrderReference($this->orderReferenceGenerator->generate())
                ->setFees($shippingFee->priceCents)
                ->setShippinfo($shippingInfo)
                ->setTva($this->includedVat($cart->getSubtotal(), $vatRate));

            $this->entityManager->persist($order);
            $this->entityManager->flush();

            return $order;
        });
    }

    /**
     * @return array{
     *     destination: string,
     *     name: string,
     *     surname: string,
     *     firstName: string,
     *     lastName: string,
     *     shippingTitle: ?string,
     *     selectedTel: ?string,
     *     tel: string,
     *     phone: string,
     *     shippingAddress: string,
     *     shippingAddress2: ?string,
     *     address: string,
     *     address2: ?string,
     *     lieuDit: ?string,
     *     postalCode: string,
     *     postcode: string,
     *     city: string,
     *     country: string,
     *     deliveryDate: ?string,
     *     selectedDelivery: ?int
     * }
     */
    private function createShippingInfo(?CheckoutShippingInfoInput $shippingInfo, string $destination): array
    {
        if (!$shippingInfo instanceof CheckoutShippingInfoInput) {
            throw new InvalidCheckoutRequestException('L’adresse de livraison ou d’expédition est obligatoire.');
        }

        $dialCode = trim((string) $shippingInfo->selectedTel);
        $phoneNumber = trim((string) $shippingInfo->tel);
        $phone = implode(' ', array_filter([$dialCode, $phoneNumber]));

        return [
            'destination' => $destination,
            'name' => trim((string) $shippingInfo->name),
            'surname' => trim((string) $shippingInfo->surname),
            'firstName' => trim((string) $shippingInfo->name),
            'lastName' => trim((string) $shippingInfo->surname),
            'shippingTitle' => $this->nullableTrim($shippingInfo->shippingTitle),
            'selectedTel' => $this->nullableTrim($shippingInfo->selectedTel),
            'tel' => $phoneNumber,
            'phone' => $phone,
            'shippingAddress' => trim((string) $shippingInfo->shippingAddress),
            'shippingAddress2' => $this->nullableTrim($shippingInfo->shippingAddress2),
            'address' => trim((string) $shippingInfo->shippingAddress),
            'address2' => $this->nullableTrim($shippingInfo->shippingAddress2),
            'lieuDit' => $this->nullableTrim($shippingInfo->lieuDit),
            'postalCode' => trim((string) $shippingInfo->postalCode),
            'postcode' => trim((string) $shippingInfo->postalCode),
            'city' => trim((string) $shippingInfo->city),
            'country' => strtoupper(trim((string) $shippingInfo->country)),
            'deliveryDate' => $shippingInfo->deliveryDate,
            'selectedDelivery' => null !== $shippingInfo->selectedDelivery
                ? (int) round($shippingInfo->selectedDelivery)
                : null,
        ];
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return '' !== $value ? $value : null;
    }

    private function includedVat(int $productsTotalTtc, float $vatRate): int
    {
        if ($vatRate <= 0) {
            return 0;
        }

        return (int) round($productsTotalTtc * $vatRate / (100 + $vatRate));
    }

    private function resolveShippingFee(string $destination): ShippingFeeDto
    {
        $normalizedDestination = mb_strtolower(trim($destination));

        foreach ($this->ordersConfigProvider->get()->shippingFees as $shippingFee) {
            if (
                $shippingFee instanceof ShippingFeeDto
                && mb_strtolower(trim($shippingFee->destination)) === $normalizedDestination
            ) {
                return $shippingFee;
            }
        }

        throw new InvalidShippingDestinationException($destination);
    }

    /**
     * @param array<int, array{variantId: int, quantity: int}> $items
     *
     * @return array<int, int>
     */
    private function aggregateItems(array $items): array
    {
        $quantitiesByVariant = [];

        foreach ($items as $itemIndex => $payload) {
            if (!is_array($payload)) {
                throw new InvalidCheckoutRequestException(sprintf('L’article à la position %d doit être un objet contenant "variantId" et "quantity".', $itemIndex));
            }

            foreach (['variantId', 'quantity'] as $field) {
                if (!array_key_exists($field, $payload)) {
                    throw new InvalidCheckoutRequestException(sprintf('L’article à la position %d ne contient pas le champ obligatoire "%s".', $itemIndex, $field));
                }
            }

            $variantId = filter_var($payload['variantId'], FILTER_VALIDATE_INT);
            $quantity = filter_var($payload['quantity'], FILTER_VALIDATE_INT);

            if (false === $variantId || $variantId <= 0 || false === $quantity || $quantity <= 0) {
                throw new InvalidCheckoutRequestException(sprintf('Article invalide : "variantId" et "quantity" doivent être des entiers strictement positifs (variantId reçu : %s, quantité reçue : %s).', json_encode($payload['variantId']), json_encode($payload['quantity'])));
            }

            $quantitiesByVariant[$variantId] = ($quantitiesByVariant[$variantId] ?? 0) + $quantity;
        }

        if ([] === $quantitiesByVariant) {
            throw new InvalidCheckoutRequestException('Le panier doit contenir au moins un article.');
        }

        ksort($quantitiesByVariant);

        return $quantitiesByVariant;
    }

    private function createAndReserveItem(int $variantId, int $quantity): CartItem
    {
        $variant = $this->entityManager->getRepository(ClothesVariant::class)->findOneWithProductForUpdate($variantId);
        $clothe = $variant?->getClothes();

        if (!$variant instanceof ClothesVariant || \App\Enum\ClotheStatus::Online !== $variant->getPublicationStatus()) {
            throw new InvalidCheckoutRequestException(sprintf('Le variant %d est introuvable, hors ligne ou associé à un vêtement indisponible.', $variantId));
        }

        if ($variant->getStock() < $quantity) {
            throw new InsufficientVariantStockException($variantId, $quantity, $variant->getStock());
        }

        $unitPriceTTC = (int) $clothe->getPrice();
        if ($unitPriceTTC <= 0) {
            throw new InvalidCheckoutRequestException(sprintf('Le vêtement associé au variant %d ne possède pas de prix valide.', $variantId));
        }

        $variant
            ->setStock($variant->getStock() - $quantity)
            ->setEditedAt(new \DateTimeImmutable());

        return (new CartItem())
            ->setVariant($variant)
            ->setProductId($variantId)
            ->setName($variant->getDisplayName())
            ->setQuantity($quantity)
            ->setUnitPriceTTC($unitPriceTTC);
    }

    public function releaseReservation(Orders $order, string $status): bool
    {
        $orderId = $order->getId();
        if (null === $orderId) {
            return false;
        }

        return $this->entityManager->wrapInTransaction(function () use ($orderId, $status): bool {
            $lockedOrder = $this->entityManager->getRepository(Orders::class)->findForUpdate($orderId);
            if (!$lockedOrder instanceof Orders || Orders::STATUS_PENDING_PAYMENT !== $lockedOrder->getStatus()) {
                return false;
            }

            foreach ($lockedOrder->getCart()?->getItems() ?? [] as $item) {
                $variantId = $item->getVariant()?->getId();
                if (null === $variantId) {
                    continue;
                }

                $variant = $this->entityManager->getRepository(ClothesVariant::class)->findOneWithProductForUpdate($variantId);
                if (!$variant instanceof ClothesVariant) {
                    continue;
                }

                $variant
                    ->setStock($variant->getStock() + (int) $item->getQuantity())
                    ->setEditedAt(new \DateTimeImmutable());
            }

            $lockedOrder->setStatus($status);

            if (
                Orders::STATUS_PAYMENT_EXPIRED === $status
                && $this->orderWorkflow->can($lockedOrder, OrderWorkflow::TRANSITION_CANCEL)
            ) {
                $this->orderWorkflow->apply($lockedOrder, OrderWorkflow::TRANSITION_CANCEL);
            }

            $this->entityManager->flush();

            return true;
        });
    }
}
