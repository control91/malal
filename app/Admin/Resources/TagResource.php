<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Resource;
use App\Models\BlogTag;

class TagResource extends Resource
{
    public static string $slug = 'etiquettes';

    public static string $model = BlogTag::class;

    public static string $area = 'blog';

    public static string $label = 'étiquette';

    public static string $labelPlural = 'Étiquettes';

    public static string $icon = 'tags';

    public static string $group = 'Éditorial';

    public static array $searchable = ['name'];

    public static array $defaultSort = ['name', 'asc'];

    public static function columns(): array
    {
        return [new C('name', 'Nom', 'title'), new C('slug', 'Adresse', 'muted')];
    }

    public static function fieldsets(): array
    {
        return [['title' => 'Étiquette', 'fields' => [F::text('name', 'Nom')->required()->max(60), F::slug('slug', 'Slug', 'name')]]];
    }
}
