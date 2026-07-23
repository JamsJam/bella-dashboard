<?php

namespace App\Enum;

enum OrderStatus: int
{
    case Created = 0;

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Créée',
        };
    }
}
