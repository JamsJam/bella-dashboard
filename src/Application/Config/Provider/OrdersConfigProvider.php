<?php

namespace App\Application\Config\Provider;

use App\Application\Config\Dto\OrdersConfigDto;

final readonly class OrdersConfigProvider extends AbstractConfigProvider
{
    private const FILE_NAME = 'orders';

    public function get(): OrdersConfigDto
    {
        return OrdersConfigDto::fromArray($this->read(self::FILE_NAME));
    }

    public function save(OrdersConfigDto $config): void
    {
        $this->write(self::FILE_NAME, $config->toArray());
    }
}
