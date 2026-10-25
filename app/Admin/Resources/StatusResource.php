<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Resource;
use App\Enums\StatusState;
use App\Models\StatusComponent;

class StatusResource extends Resource
{
    public static string $slug = 'statut';

    public static string $model = StatusComponent::class;

    public static string $area = 'catalog';

    public static string $label = 'composant';

    public static string $labelPlural = 'Statut des services';

    public static string $icon = 'activity';

    public static string $group = 'Catalogue';

    public static ?string $publishField = 'is_published';

    public static array $defaultSort = ['order', 'asc'];

    public static function columns(): array
    {
        return [new C('name', 'Composant', 'title'), new C('state', 'État', 'badge'), new C('updated_at', 'Mis à jour', 'datetime'), new C('is_published', 'Publié', 'bool')];
    }

    public static function fieldsets(): array
    {
        return [['title' => 'Composant', 'fields' => [
            F::text('name', 'Composant')->required()->max(120), F::text('description', 'Description')->max(255), F::enum('state', 'État', StatusState::class)->required(),
            F::textarea('message', 'Message public'), F::integer('order', 'Ordre'), F::boolean('is_published', 'Publié'),
        ]]];
    }
}
