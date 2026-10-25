<?php

namespace App\Enums;

enum AuditAction: string
{
    use HasOptions;

    case Login = 'login';
    case LoginFailed = 'login_failed';
    case Logout = 'logout';
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Publish = 'publish';
    case Unpublish = 'unpublish';
    case RoleChange = 'role_change';
    case Settings = 'settings';
    case Status = 'status';
    case Assign = 'assign';
    case Email = 'email';
    case Export = 'export';
    case Convert = 'convert';

    public function label(): string
    {
        return match ($this) {
            self::Login => 'Connexion',
            self::LoginFailed => 'Échec de connexion',
            self::Logout => 'Déconnexion',
            self::Create => 'Création',
            self::Update => 'Modification',
            self::Delete => 'Suppression',
            self::Publish => 'Publication',
            self::Unpublish => 'Dépublication',
            self::RoleChange => 'Changement de rôle',
            self::Settings => 'Paramètres modifiés',
            self::Status => 'Changement de statut',
            self::Assign => 'Affectation',
            self::Email => 'Email envoyé',
            self::Export => 'Export de données',
            self::Convert => 'Conversion en client',
        };
    }
}
