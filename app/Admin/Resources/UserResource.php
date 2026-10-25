<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Filters;
use App\Admin\Resource;
use App\Enums\AuditAction;
use App\Enums\Role;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserResource extends Resource
{
    public static string $slug = 'utilisateurs';

    public static string $model = User::class;

    public static string $area = 'users';

    public static string $label = 'utilisateur';

    public static string $labelPlural = 'Utilisateurs';

    public static string $icon = 'user-cog';

    public static string $group = 'Administration';

    public static string $description = 'Comptes du back-office. Désactivez un compte plutôt que de le supprimer.';

    public static bool $canDelete = false;

    public static array $searchable = ['email', 'first_name', 'last_name'];

    public static array $defaultSort = ['first_name', 'asc'];

    public static function columns(): array
    {
        return [new C('avatar', '', 'image'), new C('display_name', 'Nom', 'title'), new C('email', 'Email', 'muted'), new C('role', 'Rôle', 'badge'), new C('is_active', 'Actif', 'bool'), new C('last_login_at', 'Dernière connexion', 'datetime', 'last_login_at')];
    }

    public static function filters(): array
    {
        return [Filters::enum('role', 'Rôle', Role::class), Filters::boolean('is_active', 'Actif')];
    }

    public static function fieldsets(): array
    {
        $roles = request()->user()?->isSuperadmin() ? Role::options() : array_values(array_filter(Role::options(), fn ($o) => $o['value'] !== Role::Superadmin->value));

        return [
            ['title' => 'Identité', 'fields' => [
                F::email('email', 'Email')->required()->max(254), F::text('first_name', 'Prénom')->max(80), F::text('last_name', 'Nom')->max(80),
                F::text('job_title', 'Fonction')->max(120), F::text('phone', 'Téléphone')->max(40), F::image('avatar', 'Photo'), F::textarea('bio', 'Biographie (auteur)'),
            ]],
            ['title' => 'Accès', 'fields' => [
                F::select('role', 'Rôle', $roles)->required(), F::boolean('is_active', 'Compte actif'),
                F::password('password', 'Mot de passe')->required()->help('12 caractères minimum. Laisser vide pour ne pas le modifier.'),
            ]],
        ];
    }

    public static function rules(?Model $record): array
    {
        $rules = parent::rules($record);
        $rules['email'][] = Rule::unique('users', 'email')->ignore($record?->id);

        return $rules;
    }

    public static function defaults(Request $request): array
    {
        return ['role' => Role::Viewer->value, 'is_active' => true] + parent::defaults($request);
    }

    public static function beforeSave(Model $record, array &$data, Request $request, bool $creating): void
    {
        $actor = $request->user();
        if ($record->exists && $record->isSuperadmin() && ! $actor->isSuperadmin()) {
            throw ValidationException::withMessages(['form' => 'Seul un super administrateur peut modifier ce compte.']);
        }
        if ($record->exists && $record->is($actor)) {
            // Nobody can lock themselves out or self-promote.
            $data['role'] = $record->role->value;
            $data['is_active'] = true;
        }
        if (! $creating && isset($data['role']) && $record->role->value !== $data['role']) {
            Audit::record(AuditAction::RoleChange, $record, ['role' => [$record->role->value, $data['role']]]);
        }
    }
}
