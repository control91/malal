<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Filters;
use App\Admin\Repeater;
use App\Admin\Resource;
use App\Enums\ItemProgress;
use App\Enums\SectionTheme;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SectionResource extends Resource
{
    public static string $slug = 'sections';

    public static string $model = PageSection::class;

    public static string $area = 'content';

    public static string $label = 'section';

    public static string $labelPlural = 'Sections';

    public static string $icon = 'layers';

    public static bool $showInMenu = false;

    public static ?string $publishField = 'is_active';

    public static array $with = ['page'];

    public static array $searchable = ['title'];

    public static array $defaultSort = ['order', 'asc'];

    public static function columns(): array
    {
        return [new C('page', 'Page'), new C('kind', 'Type'), new C('title', 'Titre', 'title'), new C('is_active', 'Visible', 'bool')];
    }

    public static function filters(): array
    {
        return [Filters::model('page_id', 'Page', Page::class, 'title')];
    }

    public static function fieldsets(): array
    {
        $kinds = array_map(fn ($k, $l) => ['value' => $k, 'label' => $l], array_keys(PageSection::KINDS), PageSection::KINDS);

        return [
            ['title' => 'Section', 'fields' => [
                F::belongsTo('page_id', 'Page', Page::class, 'title')->required(), F::select('kind', 'Type de section', $kinds)->required(),
                F::enum('theme', 'Thème', SectionTheme::class)->required(), F::integer('order', "Ordre d'affichage"),
                F::boolean('is_active', 'Visible'), F::text('anchor', 'Ancre (#)')->max(60)->rules(['regex:/^[a-z0-9-]*$/']),
            ]],
            ['title' => 'Textes', 'fields' => [
                F::text('eyebrow', 'Sur-titre')->max(120), F::text('title', 'Titre')->max(200)->help('Entourez des mots d\'astérisques (*Afrique*) pour les mettre en valeur.'),
                F::textarea('subtitle', 'Sous-titre'), F::richtext('body', 'Texte'),
            ]],
            ['title' => 'Image', 'fields' => [F::image('image', 'Image'), F::text('image_alt', "Texte alternatif de l'image")->max(200)]],
            ['title' => 'Boutons', 'fields' => [
                F::text('cta_label', 'Bouton principal — libellé')->max(60), F::text('cta_url', 'Bouton principal — lien')->max(255),
                F::text('cta2_label', 'Bouton secondaire — libellé')->max(60), F::text('cta2_url', 'Bouton secondaire — lien')->max(255),
                F::integer('limit', "Nombre d'éléments affichés")->help('Pour les sections alimentées automatiquement.'),
            ]],
        ];
    }

    public static function repeaters(): array
    {
        return [new Repeater('items', 'Éléments de la section', [
            F::integer('order', 'Ordre'), F::icon(), F::text('title', 'Titre')->required()->max(160), F::textarea('text', 'Texte'),
            F::text('badge', 'Badge / période')->max(60), F::enum('progress', 'Avancement', ItemProgress::class),
            F::text('link_label', 'Libellé du lien')->max(60), F::text('link_url', 'Lien')->max(255),
        ], 'Cartes, valeurs, étapes de la feuille de route, points clés du hero…')];
    }

    public static function redirectTo(Model $record, Request $request): ?string
    {
        return route('admin.resources.edit', ['pages', $record->page_id]).'#sections';
    }
}
