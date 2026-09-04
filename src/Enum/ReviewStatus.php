<?php

namespace App\Enum;

enum ReviewStatus: string
{
    case Requested = 'requested';
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Avis demandé',
            self::Pending => 'À modérer',
            self::Accepted => 'Accepté',
            self::Rejected => 'Refusé',
        };
    }
}
