<?php

namespace App\Enums;

enum MenuLocation: string
{
    use HasOptions;

    case Header = 'header';
    case FooterSolutions = 'footer_solutions';
    case FooterCompany = 'footer_company';
    case FooterResources = 'footer_resources';
    case FooterLegal = 'footer_legal';

    public function label(): string
    {
        return match ($this) {
            self::Header => 'En-tête',
            self::FooterSolutions => 'Footer — Solutions',
            self::FooterCompany => 'Footer — Entreprise',
            self::FooterResources => 'Footer — Ressources',
            self::FooterLegal => 'Footer — Légal',
        };
    }
}
