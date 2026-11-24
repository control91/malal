<?php

namespace App\Enums;

enum SectionTheme: string
{
    use HasOptions;

    case Light = 'light';
    case Muted = 'muted';
    case Dark = 'dark';

    public function label(): string
    {
        return match ($this) {
            self::Light => 'Standard',
            self::Muted => 'Surface légère',
            self::Dark => 'Accentué',
        };
    }
}
