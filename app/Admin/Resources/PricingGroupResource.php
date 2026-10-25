<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Repeater;
use App\Admin\Resource;
use App\Models\PricingGroup;
use App\Models\Product;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Model;

class PricingGroupResource extends Resource
{
    public static string $slug = 'tarifs';

    public static string $model = PricingGroup::class;

    public static string $area = 'catalog';

    public static string $label = "groupe d'offres";

    public static string $labelPlural = 'Tarifs';

    public static string $icon = 'wallet';

    public static string $group = 'Catalogue';

    public static string $description = 'Chaque groupe est un tableau comparatif sur la page Tarifs.';

    public static ?string $publishField = 'is_published';

    public static array $searchable = ['name'];

    public static array $with = ['category', 'product'];

    public static array $defaultSort = ['order', 'asc'];

    public static string $formPage = 'Admin/Pricing/GroupForm';

    public static function columns(): array
    {
        return [new C('name', 'Groupe', 'title'), new C('category', 'Domaine'), new C('product', 'Solution'), new C('order', 'Ordre', 'text', 'order'), new C('is_published', 'Publié', 'bool')];
    }

    public static function fieldsets(): array
    {
        return [['title' => 'Groupe', 'fields' => [
            F::text('name', 'Nom')->required()->max(120), F::slug('slug', 'Slug', 'name'), F::textarea('description', 'Description'),
            F::belongsTo('service_category_id', 'Domaine', ServiceCategory::class), F::belongsTo('product_id', 'Solution', Product::class),
            F::integer('order', 'Ordre'), F::boolean('is_published', 'Publié'),
        ]]];
    }

    public static function repeaters(): array
    {
        return [new Repeater('options', 'Options payantes', [F::integer('order', 'Ordre'), F::text('name', 'Option')->required()->max(120), F::text('description', 'Description')->max(255), F::text('price_label', 'Prix (texte libre)')->max(80)])];
    }

    public static function extra(?Model $record): ?array
    {
        return $record ? ['plans' => $record->plans()->get()->map(fn ($p) => [
            'id' => $p->id, 'name' => $p->name, 'price_monthly' => $p->price_monthly, 'price_yearly' => $p->price_yearly, 'currency' => $p->currency->value,
            'price_on_request' => $p->price_on_request, 'status' => $p->status->label(), 'is_published' => $p->is_published, 'is_highlighted' => $p->is_highlighted,
        ])] : null;
    }
}
