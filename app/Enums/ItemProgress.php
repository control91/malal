<?php

namespace App\Enums;

enum ItemProgress: string
{
    use HasOptions;

    case Done = 'done';
    case InProgress = 'in_progress';
    case Planned = 'planned';

    public function label(): string
    {
        return match ($this) {
            self::Done => 'Réalisé',
            self::InProgress => 'En cours',
            self::Planned => 'Prévu',
        };
    }
}
