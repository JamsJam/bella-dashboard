<?php

namespace App\Application\PageGenerator\Services;

final class PaginateService
{
    public function getTotalPage(int $totalItem, int $limit): int
    {
        if ($totalItem < 0 || $limit < 0) {
            throw new \InvalidArgumentException('Limit or TotalItem must be positive', 1);
        }

        return (int) $totalItem = intval(ceil($totalItem / $limit));
    }
}
