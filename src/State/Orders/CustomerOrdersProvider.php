<?php

namespace App\State\Orders;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Orders\CustomerOrder;
use App\ApiResource\Orders\CustomerOrderItem;
use App\ApiResource\Orders\CustomerOrderList;
use App\Entity\Orders\CartItem;
use App\Entity\Orders\Orders;
use App\Entity\Users\Customers;
use App\Repository\Orders\OrdersRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** @implements ProviderInterface<CustomerOrderList> */
final readonly class CustomerOrdersProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private OrdersRepository $ordersRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CustomerOrderList
    {
        $customer = $this->security->getUser();
        if (!$customer instanceof Customers) {
            throw new AccessDeniedHttpException('Un client authentifié est requis.');
        }

        return new CustomerOrderList(array_map(
            fn (Orders $order): CustomerOrder => $this->mapOrder($order),
            $this->ordersRepository->findPaidByCustomer($customer),
        ));
    }

    private function mapOrder(Orders $order): CustomerOrder
    {
        $cart = $order->getCart();
        $items = [];

        foreach ($cart?->getItems() ?? [] as $item) {
            if (!$item instanceof CartItem || null === $item->getProductId()) {
                continue;
            }

            $items[] = new CustomerOrderItem(
                productId: $item->getProductId(),
                name: (string) $item->getName(),
                quantity: (int) $item->getQuantity(),
                unitPriceTTC: (int) $item->getUnitPriceTTC(),
                totalTTC: $item->getTotalTTC(),
            );
        }

        return new CustomerOrder(
            id: (int) $order->getId(),
            reference: (string) $order->getOrderReference(),
            status: (string) $order->getStatus(),
            subtotal: (int) $cart?->getSubtotal(),
            fees: (int) $order->getFees(),
            tva: (int) $order->getTva(),
            total: (int) $cart?->getTotal(),
            currency: $cart?->getCurrency() ?? 'eur',
            createdAt: $order->getCreatedAt()?->format(DATE_ATOM) ?? '',
            shippingInfo: $order->getShippinfo(),
            invoiceUrl: $order->getStripeInvoiceUrl(),
            items: $items,
        );
    }
}
