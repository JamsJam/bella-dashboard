<?php

namespace App\Enum;

enum ClotheStatus: string
{
    case Draft = 'draft';
    case Publishable = 'publishable';
    case Scheduled = 'scheduled';
    case Online = 'online';
    case Offline = 'offline';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Publishable => 'Publiable',
            self::Scheduled => 'Planifié',
            self::Online => 'En ligne',
            self::Offline => 'Hors ligne',
            self::Archived => 'Archivé',
        };
    }

    public function progressionRank(): int
    {
        return match ($this) {
            self::Draft => 0,
            self::Publishable => 1,
            self::Scheduled => 2,
            self::Online => 3,
            self::Offline => 4,
            self::Archived => 5,
        };
    }
}
