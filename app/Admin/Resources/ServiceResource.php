<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Filters;
use App\Admin\Resource;
use App\Enums\Availability;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class ServiceResource extends Resource
{
    public static string $slug = 'services';

    public static string $model = Service::class;

    public static string $area = 'catalog';

    public static string $label = 'service';

    public static string $labelPlural = 'Services';

    public static string $icon = 'server';

    public static string $group = 'Catalogue';

    public static ?string $publishField = 'is_published';

    public static array $searchable = ['name', 'summary'];

    public static array $with = ['category'];

    public static array $defaultSort = ['order', 'asc'];

    public static function columns(): array
    {
        return [new C('name', 'Service', 'title', 'name'), new C('category', 'Domaine'), new C('status', 'Disponibilité', 'badge'), new C('is_featured', 'En avant', 'bool'), new C('is_published', 'Publié', 'bool')];
    }

    public static function filters(): array
    {
        return [Filters::model('service_category_id', 'Domaine', ServiceCategory::class), Filters::enum('status', 'Disponibilité', Availability::class), Filters::boolean('is_published', 'Publié')];
    }

    public static function fieldsets(): array
    {
        return [['title' => 'Service', 'fields' => [
            F::belongsTo('service_category_id', 'Domaine', ServiceCategory::class)->required(), F::text('name', 'Nom')->required()->max(160), F::slug('slug', 'Slug', 'name'),
            F::icon(), F::textarea('summary', 'Description courte')->required(), F::textarea('details', 'Points clés (un par ligne)'),
            F::enum('status', 'Disponibilité', Availability::class)->required(), F::boolean('is_featured', 'Mis en avant'), F::integer('order', 'Ordre'), F::boolean('is_published', 'Publié'),
        ]]];
    }

    public static function rules(?Model $record): array
    {
        $rules = parent::rules($record);
        // Slugs are unique per domain, not globally.
        $rules['slug'] = ['required', 'string', 'max:160', 'regex:/^[a-z0-9-]+$/',
            Rule::unique('services')->where('service_category_id', request('service_category_id'))->ignore($record?->id)];

        return $rules;
    }
}
