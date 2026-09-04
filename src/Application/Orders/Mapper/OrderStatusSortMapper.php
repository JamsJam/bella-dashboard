<?php

namespace App\Application\Orders\Mapper;

use App\Enum\OrderStatus;

final class OrderStatusSortMapper
{
    private const RANKS = [
        OrderStatus::Created->value => 10,
        OrderStatus::Processing->value => 20,
        OrderStatus::AwaitingDelivery->value => 30,
        OrderStatus::Shipped->value => 40,
        OrderStatus::Delivered->value => 50,
        OrderStatus::Cancelled->value => 60,
    ];

    public function rank(OrderStatus $status): int
    {
        return self::RANKS[$status->value];
    }

    public function dqlCase(string $field): string
    {
        $cases = [];

        foreach (self::RANKS as $status => $rank) {
            $cases[] = sprintf("WHEN '%s' THEN %d", $status, $rank);
        }

        return sprintf('CASE %s %s ELSE 999 END', $field, implode(' ', $cases));
    }
}
