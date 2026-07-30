<?php

namespace App\Enum;

enum OrderStatus: string
{
    case Created = 'created';
    case Processing = 'processing';
    case Cancelled = 'cancelled';
    case AwaitingDelivery = 'awaiting_delivery';
    case Shipped = 'shipped';
    case Delivered = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Créée',
            self::Processing => 'En attente de traitement',
            self::Cancelled => 'Annulée',
            self::AwaitingDelivery => 'Livraison en attente',
            self::Shipped => 'Expédiée',
            self::Delivered => 'Livrée',
        };
    }
}
