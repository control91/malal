<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;

/**
 * Role-based access control for the back-office.
 *
 * A role grants capabilities "<area>.<action>" with action = view|edit
 * (edit implies view). Every admin route declares the capability it needs
 * through the "can:" middleware; the check runs server-side on each request.
 */
final class Capabilities
{
    public const AREAS = [
        'content' => 'Contenus du site',
        'catalog' => 'Catalogue et tarifs',
        'blog' => 'Articles',
        'portfolio' => 'Réalisations',
        'media' => 'Médiathèque',
        'inquiries' => 'Demandes entrantes',
        'crm' => 'Prospects (CRM)',
        'newsletter' => 'Newsletter',
        'analytics' => 'Statistiques',
        'users' => 'Utilisateurs',
        'settings' => 'Configuration',
        'audit' => "Journal d'audit",
    ];

    private const EDITORIAL = ['content', 'catalog', 'blog', 'portfolio', 'media'];

    /** @return array<int, string> */
    public static function forRole(Role $role): array
    {
        $all = array_keys(self::AREAS);

        return match ($role) {
            Role::Superadmin, Role::Admin => self::caps(edit: $all),
            Role::Editor => self::caps(edit: self::EDITORIAL, view: ['analytics']),
            Role::Sales => self::caps(edit: ['inquiries', 'crm', 'newsletter'], view: ['catalog', 'analytics']),
            Role::Support => self::caps(edit: ['inquiries'], view: ['crm', 'catalog']),
            Role::Viewer => self::caps(view: array_values(array_diff($all, ['users', 'settings', 'audit']))),
        };
    }

    /** @return array<int, string> */
    public static function forUser(?User $user): array
    {
        if ($user === null || ! $user->is_active) {
            return [];
        }

        return self::forRole($user->role);
    }

    public static function has(?User $user, string $capability): bool
    {
        return in_array($capability, self::forUser($user), true);
    }

    /** @return array<int, string> */
    private static function caps(array $edit = [], array $view = []): array
    {
        $caps = [];
        foreach ($edit as $area) {
            $caps[] = "$area.view";
            $caps[] = "$area.edit";
        }
        foreach ($view as $area) {
            $caps[] = "$area.view";
        }

        return array_values(array_unique($caps));
    }
}
