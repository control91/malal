<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Filters;
use App\Admin\Resource;
use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;

class AuditResource extends Resource
{
    public static string $slug = 'audit';

    public static string $model = AuditLog::class;

    public static string $area = 'audit';

    public static string $label = 'entrée';

    public static string $labelPlural = "Journal d'audit";

    public static string $icon = 'history';

    public static string $group = 'Administration';

    public static string $description = "Lecture seule : aucune entrée ne peut être modifiée ou supprimée depuis l'interface.";

    public static bool $canCreate = false;

    public static bool $canEdit = false;

    public static bool $canDelete = false;

    public static int $perPage = 50;

    public static array $searchable = ['actor_label', 'object_repr', 'object_type'];

    public static array $defaultSort = ['created_at', 'desc'];

    public static array $exportable = ['created_at', 'actor_label', 'action', 'object_type', 'object_id', 'object_repr', 'ip_address'];

    public static function columns(): array
    {
        return [new C('created_at', 'Date', 'datetime', 'created_at'), new C('actor_label', 'Auteur', 'title'), new C('action', 'Action', 'badge'), new C('object_type', 'Type', 'muted'), new C('object_repr', 'Objet'), new C('ip_address', 'IP', 'muted')];
    }

    public static function filters(): array
    {
        return [Filters::enum('action', 'Action', AuditAction::class), Filters::model('actor_id', 'Auteur', User::class, 'first_name')];
    }
}
