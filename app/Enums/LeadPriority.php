<?php

namespace App\Enums;

enum LeadPriority: string
{
    use HasOptions;

    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Basse',
            self::Normal => 'Normale',
            self::High => 'Haute',
        };
    }
}
