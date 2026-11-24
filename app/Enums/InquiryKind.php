<?php

namespace App\Enums;

enum InquiryKind: string
{
    use HasOptions;

    case Contact = 'contact';
    case Quote = 'quote';
    case Demo = 'demo';
    case Partnership = 'partnership';
    case Career = 'career';
    case Support = 'support';

    public function label(): string
    {
        return match ($this) {
            self::Contact => 'Contact',
            self::Quote => 'Demande de devis',
            self::Demo => 'Demande de démonstration',
            self::Partnership => 'Partenariat',
            self::Career => 'Candidature',
            self::Support => 'Support',
        };
    }
}
