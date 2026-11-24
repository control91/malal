<?php

namespace App\Enums;

enum Availability: string
{
    use HasOptions;

    case Available = 'available';
    case Beta = 'beta';
    case ComingSoon = 'coming_soon';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Beta => 'Bêta',
            self::ComingSoon => 'À venir',
        };
    }
}
