<?php

namespace App\Enums;

enum LeadStatus: string
{
    use HasOptions;

    case New = 'new';
    case Qualified = 'qualified';
    case Contacted = 'contacted';
    case Demo = 'demo';
    case Proposal = 'proposal';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouveau',
            self::Qualified => 'Qualifié',
            self::Contacted => 'Contacté',
            self::Demo => 'Démonstration',
            self::Proposal => 'Proposition',
            self::Won => 'Gagné',
            self::Lost => 'Perdu',
        };
    }
}
