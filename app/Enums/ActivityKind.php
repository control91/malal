<?php

namespace App\Enums;

enum ActivityKind: string
{
    use HasOptions;

    case Note = 'note';
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case Demo = 'demo';
    case Status = 'status';
    case Assignment = 'assignment';
    case FollowUp = 'follow_up';
    case Inquiry = 'inquiry';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Note => 'Note',
            self::Call => 'Appel',
            self::Email => 'Email',
            self::Meeting => 'Rendez-vous',
            self::Demo => 'Démonstration',
            self::Status => 'Changement de statut',
            self::Assignment => 'Affectation',
            self::FollowUp => 'Relance',
            self::Inquiry => 'Demande reçue',
            self::System => 'Système',
        };
    }
}
