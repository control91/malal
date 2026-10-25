<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Repeater;
use App\Admin\Resource;
use App\Enums\Availability;
use App\Enums\Currency;
use App\Models\PricingGroup;
use App\Models\PricingPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PlanResource extends Resource
{
    public static string $slug = 'offres';

    public static string $model = PricingPlan::class;

    public static string $area = 'catalog';

    public static string $label = 'offre';

    public static string $labelPlural = 'Offres tarifaires';

    public static string $icon = 'receipt';

    public static string $group = 'Catalogue';

    public static bool $showInMenu = false;

    public static ?string $publishField = 'is_published';

    public static array $with = ['group'];

    public static array $defaultSort = ['order', 'asc'];

    public static function columns(): array
    {
        return [new C('name', 'Offre', 'title'), new C('group', 'Groupe'), new C('price_monthly', 'Mensuel', 'money'), new C('status', 'Disponibilité', 'badge'), new C('is_published', 'Publié', 'bool')];
    }

    public static function fieldsets(): array
    {
        return [
            ['title' => 'Offre', 'fields' => [
                F::belongsTo('pricing_group_id', 'Groupe', PricingGroup::class)->required(), F::text('name', "Nom de l'offre")->required()->max(120),
                F::text('description', 'Description')->max(255)->wide(), F::enum('status', 'Disponibilité', Availability::class)->required(),
                F::boolean('is_highlighted', 'Offre recommandée'), F::integer('order', 'Ordre'), F::boolean('is_published', 'Publié'),
            ]],
            ['title' => 'Prix', 'fields' => [
                F::enum('currency', 'Devise', Currency::class)->required(), F::number('price_monthly', 'Prix mensuel'), F::number('price_yearly', 'Prix annuel (total)'),
                F::boolean('price_on_request', 'Prix sur devis (masque les montants)'), F::text('price_note', 'Mention sous le prix')->max(120)->help('Ex. HT, par utilisateur…'),
            ]],
            ['title' => 'Bouton', 'fields' => [F::text('cta_label', 'Libellé du bouton')->required()->max(60), F::text('cta_url', 'Lien du bouton')->max(255)->help('Vide = formulaire de devis pré-rempli.')]],
        ];
    }

    public static function repeaters(): array
    {
        return [new Repeater('features', 'Caractéristiques comparées', [
            F::integer('order', 'Ordre'), F::text('label', 'Caractéristique')->required()->max(160), F::text('value', 'Valeur')->max(80), F::boolean('included', 'Inclus'),
        ], 'Utilisez le même libellé dans chaque offre du groupe pour aligner le tableau comparatif.')];
    }

    public static function redirectTo(Model $record, Request $request): ?string
    {
        return route('admin.resources.edit', ['tarifs', $record->pricing_group_id]);
    }
}
