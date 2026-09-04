<?php

namespace App\Tests\Application\Orders\Mapper;

use App\Application\Orders\Mapper\OrderStatusSortMapper;
use App\Enum\OrderStatus;
use PHPUnit\Framework\TestCase;

final class OrderStatusSortMapperTest extends TestCase
{
    public function testStatusesFollowBusinessHierarchy(): void
    {
        $mapper = new OrderStatusSortMapper();
        $statuses = OrderStatus::cases();

        usort($statuses, static fn (OrderStatus $left, OrderStatus $right): int => $mapper->rank($left) <=> $mapper->rank($right));

        self::assertSame([
            OrderStatus::Created,
            OrderStatus::Processing,
            OrderStatus::AwaitingDelivery,
            OrderStatus::Shipped,
            OrderStatus::Delivered,
            OrderStatus::Cancelled,
        ], $statuses);
    }

    public function testDqlCaseUsesTheSameRanks(): void
    {
        $mapper = new OrderStatusSortMapper();
        $dql = $mapper->dqlCase('orders.orderStatus');

        foreach (OrderStatus::cases() as $status) {
            self::assertStringContainsString(
                sprintf("WHEN '%s' THEN %d", $status->value, $mapper->rank($status)),
                $dql,
            );
        }
    }
}
