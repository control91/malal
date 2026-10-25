<?php

namespace App\Enums;

enum LeadSource: string
{
    use HasOptions;

    case Website = 'website';
    case Referral = 'referral';
    case Event = 'event';
    case Social = 'social';
    case Outbound = 'outbound';
    case Partner = 'partner';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Website => 'Site web',
            self::Referral => 'Recommandation',
            self::Event => 'Événement',
            self::Social => 'Réseaux sociaux',
            self::Outbound => 'Prospection',
            self::Partner => 'Partenaire',
            self::Other => 'Autre',
        };
    }
}
