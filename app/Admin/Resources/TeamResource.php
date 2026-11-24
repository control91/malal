<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Resource;
use App\Models\TeamMember;

class TeamResource extends Resource
{
    public static string $slug = 'equipe';

    public static string $model = TeamMember::class;

    public static string $area = 'content';

    public static string $label = 'membre';

    public static string $labelPlural = 'Équipe';

    public static string $icon = 'users';

    public static ?string $publishField = 'is_published';

    public static array $searchable = ['name', 'role'];

    public static array $defaultSort = ['order', 'asc'];

    public static function columns(): array
    {
        return [new C('photo', '', 'image'), new C('name', 'Nom', 'title'), new C('role', 'Fonction'), new C('is_leadership', 'Direction', 'bool'), new C('is_published', 'Publié', 'bool')];
    }

    public static function fieldsets(): array
    {
        return [['title' => 'Membre', 'fields' => [
            F::text('name', 'Nom')->required()->max(120), F::text('role', 'Fonction')->required()->max(120), F::textarea('bio', 'Biographie'),
            F::image('photo', 'Photo'), F::url('linkedin_url', 'LinkedIn'), F::boolean('is_leadership', 'Membre de la direction'), F::integer('order', 'Ordre'), F::boolean('is_published', 'Publié'),
        ]]];
    }
}
