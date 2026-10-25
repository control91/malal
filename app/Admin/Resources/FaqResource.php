<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Filters;
use App\Admin\Resource;
use App\Models\Faq;
use App\Models\ServiceCategory;

class FaqResource extends Resource
{
    public static string $slug = 'faq';

    public static string $model = Faq::class;

    public static string $area = 'content';

    public static string $label = 'question';

    public static string $labelPlural = 'FAQ';

    public static string $icon = 'circle-help';

    public static ?string $publishField = 'is_published';

    public static array $searchable = ['question'];

    public static array $with = ['category'];

    public static array $defaultSort = ['order', 'asc'];

    public static function columns(): array
    {
        return [new C('question', 'Question', 'title'), new C('category', 'Domaine'), new C('order', 'Ordre', 'text', 'order'), new C('is_published', 'Publiée', 'bool')];
    }

    public static function filters(): array
    {
        return [Filters::model('service_category_id', 'Domaine', ServiceCategory::class), Filters::boolean('is_published', 'Publiée')];
    }

    public static function fieldsets(): array
    {
        return [['title' => 'Question', 'fields' => [
            F::text('question', 'Question')->required()->max(255)->wide(), F::richtext('answer', 'Réponse')->required(),
            F::belongsTo('service_category_id', 'Domaine', ServiceCategory::class)->help('Vide = question générale.'), F::integer('order', 'Ordre'), F::boolean('is_published', 'Publiée'),
        ]]];
    }
}
