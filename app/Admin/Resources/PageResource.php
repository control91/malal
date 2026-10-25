<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Filters;
use App\Admin\Resource;
use App\Enums\PageTemplate;
use App\Enums\PublishStatus;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Eloquent\Model;

class PageResource extends Resource
{
    public static string $slug = 'pages';

    public static string $model = Page::class;

    public static string $area = 'content';

    public static string $label = 'page';

    public static string $labelPlural = 'Pages';

    public static string $icon = 'panels-top-left';

    public static string $group = 'Site';

    public static string $description = 'Pages du site et leurs sections (accueil, entreprise, mentions légales…).';

    public static ?string $publishField = 'status';

    public static array $searchable = ['title', 'slug'];

    public static array $defaultSort = ['title', 'asc'];

    public static string $formPage = 'Admin/Pages/Form';

    public static function columns(): array
    {
        return [new C('title', 'Titre', 'title', 'title'), new C('slug', 'Adresse', 'muted'), new C('template', 'Gabarit'), new C('status', 'Statut', 'badge'), new C('updated_at', 'Modifiée', 'datetime', 'updated_at')];
    }

    public static function filters(): array
    {
        return [Filters::enum('status', 'Statut', PublishStatus::class), Filters::enum('template', 'Gabarit', PageTemplate::class)];
    }

    public static function fieldsets(): array
    {
        return [
            ['title' => 'Page', 'fields' => [
                F::text('title', 'Titre')->required()->max(200), F::slug(), F::enum('template', 'Gabarit', PageTemplate::class)->required(),
                F::enum('status', 'Statut', PublishStatus::class)->required(), F::text('eyebrow', 'Sur-titre')->max(120),
                F::textarea('intro', 'Introduction'), F::richtext('body', 'Contenu'),
            ]],
            ['title' => 'Référencement (SEO)', 'fields' => [F::text('meta_title', 'Meta title')->max(70)->counter(60), F::text('meta_description', 'Meta description')->max(170)->counter(155)->wide(), F::image('og_image', 'Image de partage'), F::boolean('noindex', 'Masquer aux moteurs de recherche')]],
        ];
    }

    public static function extra(?Model $record): ?array
    {
        if (! $record) {
            return null;
        }

        return ['sections' => $record->sections()->withCount('items')->get()->map(fn ($s) => [
            'id' => $s->id, 'title' => $s->title, 'kind' => PageSection::KINDS[$s->kind] ?? $s->kind, 'theme' => $s->theme,
            'is_active' => $s->is_active, 'items_count' => $s->items_count,
        ])];
    }
}
