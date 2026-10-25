<?php

namespace App\Enums;

enum Budget: string
{
    use HasOptions;

    case Small = 'lt_10m';
    case Medium = '10m_50m';
    case Large = '50m_200m';
    case XLarge = 'gt_200m';

    public function label(): string
    {
        return match ($this) {
            self::Small => 'Moins de 10 M GNF',
            self::Medium => '10 à 50 M GNF',
            self::Large => '50 à 200 M GNF',
            self::XLarge => 'Plus de 200 M GNF',
        };
    }
}
