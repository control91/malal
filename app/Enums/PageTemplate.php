<?php

namespace App\Enums;

enum PageTemplate: string
{
    use HasOptions;

    case Home = 'home';
    case Standard = 'standard';
    case Legal = 'legal';

    public function label(): string
    {
        return match ($this) {
            self::Home => 'Accueil',
            self::Standard => 'Standard (sections)',
            self::Legal => 'Texte légal / long',
        };
    }
}
