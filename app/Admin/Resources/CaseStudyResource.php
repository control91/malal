<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Filters;
use App\Admin\Repeater;
use App\Admin\Resource;
use App\Enums\PublishStatus;
use App\Models\CaseStudy;
use App\Models\ServiceCategory;

class CaseStudyResource extends Resource
{
    public static string $slug = 'realisations';

    public static string $model = CaseStudy::class;

    public static string $area = 'portfolio';

    public static string $label = 'réalisation';

    public static string $labelPlural = 'Réalisations';

    public static string $icon = 'award';

    public static string $group = 'Éditorial';

    public static ?string $publishField = 'status';

    public static array $searchable = ['title', 'client_name', 'sector'];

    public static array $defaultSort = ['order', 'asc'];

    public static function columns(): array
    {
        return [new C('cover', '', 'image'), new C('title', 'Projet', 'title'), new C('sector', 'Secteur'), new C('publication_consent', 'Consentement', 'bool'), new C('is_example', 'Exemple', 'bool'), new C('status', 'Statut', 'badge')];
    }

    public static function filters(): array
    {
        return [Filters::enum('status', 'Statut', PublishStatus::class), Filters::boolean('is_example', 'Exemple'), Filters::boolean('publication_consent', 'Consentement')];
    }

    public static function fieldsets(): array
    {
        return [
            ['title' => 'Projet', 'fields' => [
                F::text('title', 'Nom du projet')->required()->max(200), F::slug(), F::text('sector', "Secteur d'activité")->required()->max(120), F::number('year', 'Année')->rules(['integer', 'between:2000,2100']),
                F::text('summary', 'Résumé')->required()->max(255)->wide(), F::belongsToMany('services', 'Domaines', ServiceCategory::class), F::text('technologies', 'Technologies (séparées par des virgules)')->max(255)->wide(), F::image('cover', 'Image principale'),
            ]],
            ['title' => 'Client et consentement', 'fields' => [
                F::text('client_name', 'Client')->max(160), F::boolean('show_client_name', 'Nommer le client publiquement'), F::boolean('publication_consent', 'Consentement de publication obtenu'),
                F::text('consent_reference', 'Référence du consentement')->max(200)->help('Ex. email du 12/03/2026, contrat §7…'), F::boolean('is_example', 'Exemple illustratif (badge public)'),
            ]],
            ['title' => 'Contenu', 'fields' => [F::richtext('body', 'Détails du projet')->required()
                ->help("Organisez librement le contenu avec vos propres titres (H2, H3), listes et images. N'indiquez que des résultats mesurés et validés par le client.")]],
            ['title' => 'Publication', 'fields' => [F::enum('status', 'Statut', PublishStatus::class)->required(), F::integer('order', 'Ordre')]],
            ['title' => 'Référencement (SEO)', 'fields' => [F::text('meta_title', 'Meta title')->max(70)->counter(60), F::text('meta_description', 'Meta description')->max(170)->counter(155)->wide(), F::image('og_image', 'Image de partage'), F::boolean('noindex', 'Masquer aux moteurs de recherche')]],
        ];
    }

    public static function repeaters(): array
    {
        return [new Repeater('images', "Galerie d'images", [F::integer('order', 'Ordre'), F::image('image', 'Image'), F::text('caption', 'Légende')->max(200)])];
    }
}
