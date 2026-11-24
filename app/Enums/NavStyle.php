<?php

namespace App\Enums;

enum NavStyle: string
{
    use HasOptions;

    case Link = 'link';
    case Button = 'button';

    public function label(): string
    {
        return match ($this) {
            self::Link => 'Lien',
            self::Button => 'Bouton',
        };
    }
}
