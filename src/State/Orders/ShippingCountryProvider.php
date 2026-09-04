<?php

namespace App\State\Orders;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Orders\ShippingCountry;
use App\ApiResource\Orders\ShippingCountryList;
use App\Application\Config\Dto\ShippingFeeDto;
use App\Application\Config\Provider\OrdersConfigProvider;
use Symfony\Component\HttpFoundation\RequestStack;

/** @implements ProviderInterface<ShippingCountryList> */
final readonly class ShippingCountryProvider implements ProviderInterface
{
    public function __construct(
        private OrdersConfigProvider $ordersConfigProvider,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ShippingCountryList
    {
        $countries = [];

        foreach ($this->ordersConfigProvider->get()->shippingFees as $shippingFee) {
            if (!$shippingFee instanceof ShippingFeeDto) {
                continue;
            }

            $countries[] = new ShippingCountry(
                destination: $shippingFee->destination,
                priceCents: $shippingFee->priceCents,
                flag: $this->absoluteUrl($shippingFee->flag),
            );
        }

        return new ShippingCountryList($countries);
    }

    private function absoluteUrl(?string $path): ?string
    {
        if (null === $path || '' === $path) {
            return null;
        }

        if (false !== filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return $path;
        }

        return $request->getSchemeAndHttpHost() . '/' . ltrim($path, '/');
    }
}
