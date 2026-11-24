<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Repeater;
use App\Admin\Resource;
use App\Enums\Availability;
use App\Models\ServiceCategory;

class CategoryResource extends Resource
{
    public static string $slug = 'domaines';

    public static string $model = ServiceCategory::class;

    public static string $area = 'catalog';

    public static string $label = 'domaine';

    public static string $labelPlural = "Domaines d'expertise";

    public static string $icon = 'layout-grid';

    public static string $group = 'Catalogue';

    public static ?string $publishField = 'is_published';

    public static array $searchable = ['name'];

    public static array $defaultSort = ['order', 'asc'];

    public static function columns(): array
    {
        return [new C('name', 'Domaine', 'title', 'name'), new C('slug', 'Adresse', 'muted'), new C('order', 'Ordre', 'text', 'order'), new C('show_on_home', 'Accueil', 'bool'), new C('is_published', 'Publié', 'bool')];
    }

    public static function fieldsets(): array
    {
        return [
            ['title' => 'Domaine', 'fields' => [
                F::text('name', 'Nom')->required()->max(120), F::slug('slug', 'Adresse (slug)', 'name'), F::icon(), F::text('tagline', 'Accroche')->max(200),
                F::textarea('summary', 'Résumé (cartes)'), F::integer('order', 'Ordre'), F::boolean('is_published', 'Publié'), F::boolean('show_on_home', "Afficher sur l'accueil"),
            ]],
            ['title' => 'Page dédiée', 'fields' => [
                F::text('hero_title', 'Titre de la page')->max(200)->wide(), F::textarea('hero_subtitle', 'Sous-titre'), F::richtext('description', 'Présentation détaillée'),
                F::image('image', 'Illustration'), F::boolean('show_hosting_disclosure', 'Afficher la transparence hébergement'), F::boolean('demo_space_enabled', "Afficher l'espace démonstrations"),
            ]],
            ['title' => 'Référencement (SEO)', 'fields' => [F::text('meta_title', 'Meta title')->max(70)->counter(60), F::text('meta_description', 'Meta description')->max(170)->counter(155)->wide(), F::image('og_image', 'Image de partage'), F::boolean('noindex', 'Masquer aux moteurs de recherche')]],
        ];
    }

    public static function repeaters(): array
    {
        return [new Repeater('services', 'Services du domaine', [
            F::integer('order', 'Ordre'), F::text('name', 'Nom')->required()->max(160), F::text('slug', 'Slug')->required()->max(160)->rules(['regex:/^[a-z0-9-]+$/']),
            F::icon(), F::textarea('summary', 'Description courte')->required(), F::textarea('details', 'Points clés (un par ligne)'),
            F::enum('status', 'Disponibilité', Availability::class)->required(), F::boolean('is_featured', 'Mis en avant'), F::boolean('is_published', 'Publié'),
        ], 'Distinguez clairement : Disponible, Bêta, À venir.')];
    }
}
