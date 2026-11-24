<?php

namespace App\Enums;

enum Role: string
{
    use HasOptions;

    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case Editor = 'editor';
    case Sales = 'sales';
    case Support = 'support';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Super administrateur',
            self::Admin => 'Administrateur',
            self::Editor => 'Éditeur de contenu',
            self::Sales => 'Commercial',
            self::Support => 'Support',
            self::Viewer => 'Lecteur',
        };
    }
}
