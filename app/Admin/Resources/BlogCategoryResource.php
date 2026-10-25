<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Resource;
use App\Models\BlogCategory;

class BlogCategoryResource extends Resource
{
    public static string $slug = 'categories-blog';

    public static string $model = BlogCategory::class;

    public static string $area = 'blog';

    public static string $label = 'catégorie';

    public static string $labelPlural = 'Catégories';

    public static string $icon = 'folder';

    public static string $group = 'Éditorial';

    public static array $defaultSort = ['order', 'asc'];

    public static function columns(): array
    {
        return [new C('name', 'Nom', 'title'), new C('slug', 'Adresse', 'muted'), new C('order', 'Ordre')];
    }

    public static function fieldsets(): array
    {
        return [['title' => 'Catégorie', 'fields' => [F::text('name', 'Nom')->required()->max(80), F::slug('slug', 'Slug', 'name'), F::textarea('description', 'Description'), F::integer('order', 'Ordre')]]];
    }
}
