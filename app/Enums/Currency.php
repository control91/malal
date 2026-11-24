<?php

namespace App\Enums;

enum Currency: string
{
    use HasOptions;

    case GNF = 'GNF';
    case XOF = 'XOF';
    case EUR = 'EUR';
    case USD = 'USD';

    public function label(): string
    {
        return match ($this) {
            self::GNF => 'GNF',
            self::XOF => 'FCFA (XOF)',
            self::EUR => 'EUR',
            self::USD => 'USD',
        };
    }
}
