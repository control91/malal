<?php

namespace App\Enums;

enum PartnerKind: string
{
    use HasOptions;

    case Technology = 'technology';
    case Commercial = 'commercial';
    case Institutional = 'institutional';
    case Academic = 'academic';

    public function label(): string
    {
        return match ($this) {
            self::Technology => 'Technologique',
            self::Commercial => 'Commercial',
            self::Institutional => 'Institutionnel',
            self::Academic => 'Académique',
        };
    }
}
