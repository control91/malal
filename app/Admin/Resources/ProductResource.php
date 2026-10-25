<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Filters;
use App\Admin\Repeater;
use App\Admin\Resource;
use App\Enums\Availability;
use App\Models\Product;
use App\Models\ServiceCategory;

class ProductResource extends Resource
{
    public static string $slug = 'solutions';

    public static string $model = Product::class;

    public static string $area = 'catalog';

    public static string $label = 'solution';

    public static string $labelPlural = 'Solutions';

    public static string $icon = 'package';

    public static string $group = 'Catalogue';

    public static ?string $publishField = 'is_published';

    public static array $searchable = ['name', 'tagline'];

    public static array $with = ['category'];

    public static array $defaultSort = ['order', 'asc'];

    public static function columns(): array
    {
        return [new C('logo', '', 'image'), new C('name', 'Solution', 'title', 'name'), new C('category', 'Domaine'), new C('status', 'Disponibilité', 'badge'), new C('is_featured', 'Accueil', 'bool'), new C('is_published', 'Publié', 'bool')];
    }

    public static function filters(): array
    {
        return [Filters::model('service_category_id', 'Domaine', ServiceCategory::class), Filters::enum('status', 'Disponibilité', Availability::class), Filters::boolean('is_published', 'Publié'), Filters::boolean('is_example', 'Exemple')];
    }

    public static function fieldsets(): array
    {
        return [
            ['title' => 'Présentation', 'fields' => [
                F::text('name', 'Nom')->required()->max(120), F::slug('slug', 'Adresse (slug)', 'name'), F::belongsTo('service_category_id', 'Domaine', ServiceCategory::class)->required(),
                F::icon(), F::text('tagline', 'Accroche')->required()->max(200)->wide(), F::textarea('summary', 'Résumé')->required(), F::richtext('description', 'Description détaillée'),
                F::textarea('benefits', 'Avantages (un par ligne)'), F::text('audience', 'Pour qui ?')->max(255)->wide(), F::image('logo', 'Logo / visuel'),
            ]],
            ['title' => 'Publication', 'fields' => [
                F::enum('status', 'Disponibilité', Availability::class)->required(), F::boolean('is_published', 'Publié'), F::boolean('is_featured', "Mis en avant sur l'accueil"),
                F::boolean('is_example', "Contenu d'exemple (badge « Exemple »)"), F::integer('order', 'Ordre'),
            ]],
            ['title' => 'Commercial', 'fields' => [F::boolean('demo_enabled', 'Demande de démo possible'), F::boolean('show_pricing', 'Afficher les tarifs publics'), F::url('external_url', "Lien vers l'application")]],
            ['title' => 'Référencement (SEO)', 'fields' => [F::text('meta_title', 'Meta title')->max(70)->counter(60), F::text('meta_description', 'Meta description')->max(170)->counter(155)->wide(), F::image('og_image', 'Image de partage'), F::boolean('noindex', 'Masquer aux moteurs de recherche')]],
        ];
    }

    public static function repeaters(): array
    {
        return [
            new Repeater('features', 'Fonctionnalités', [F::integer('order', 'Ordre'), F::icon(), F::text('title', 'Fonctionnalité')->required()->max(120), F::textarea('description', 'Description')]),
            new Repeater('screenshots', "Captures d'écran", [F::integer('order', 'Ordre'), F::image('image', 'Image'), F::text('caption', 'Légende')->max(200)]),
        ];
    }
}
