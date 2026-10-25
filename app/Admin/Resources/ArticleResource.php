<?php

namespace App\Admin\Resources;

use App\Admin\Column as C;
use App\Admin\Field as F;
use App\Admin\Filters;
use App\Admin\Resource;
use App\Enums\PublishStatus;
use App\Models\BlogArticle;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ArticleResource extends Resource
{
    public static string $slug = 'articles';

    public static string $model = BlogArticle::class;

    public static string $area = 'blog';

    public static string $label = 'article';

    public static string $labelPlural = 'Articles';

    public static string $icon = 'newspaper';

    public static string $group = 'Éditorial';

    public static ?string $publishField = 'status';

    public static array $searchable = ['title', 'excerpt'];

    public static array $with = ['category', 'author'];

    public static array $defaultSort = ['created_at', 'desc'];

    public static string $formPage = 'Admin/Articles/Form';

    public static function columns(): array
    {
        return [new C('cover', '', 'image'), new C('title', 'Titre', 'title', 'title'), new C('category', 'Catégorie'), new C('author', 'Auteur'), new C('display_status', 'Statut', 'badge'), new C('published_at', 'Publication', 'datetime', 'published_at')];
    }

    public static function row(Model $record): array
    {
        return ['display_status' => $record->displayStatus()] + parent::row($record);
    }

    public static function filters(): array
    {
        return [Filters::enum('status', 'Statut', PublishStatus::class), Filters::model('blog_category_id', 'Catégorie', BlogCategory::class), Filters::boolean('is_featured', 'À la une')];
    }

    public static function fieldsets(): array
    {
        return [
            ['title' => 'Article', 'fields' => [F::text('title', 'Titre')->required()->max(200)->wide(), F::slug(), F::textarea('excerpt', 'Chapô')->max(400), F::richtext('body', 'Contenu')->required()]],
            ['title' => 'Classement', 'fields' => [
                F::belongsTo('blog_category_id', 'Catégorie', BlogCategory::class), F::belongsTo('author_id', 'Auteur', User::class, 'first_name'),
                F::belongsToMany('tags', 'Étiquettes', BlogTag::class), F::boolean('is_featured', 'À la une'),
            ]],
            ['title' => 'Couverture', 'fields' => [F::image('cover', 'Image de couverture'), F::text('cover_alt', 'Texte alternatif')->max(200)]],
            ['title' => 'Publication', 'fields' => [F::enum('status', 'Statut', PublishStatus::class)->required(), F::datetime('published_at', 'Date de publication')->help('Une date future programme la publication.')]],
            ['title' => 'Référencement (SEO)', 'fields' => [F::text('meta_title', 'Meta title')->max(70)->counter(60), F::text('meta_description', 'Meta description')->max(170)->counter(155)->wide(), F::image('og_image', 'Image de partage'), F::boolean('noindex', 'Masquer aux moteurs de recherche')]],
        ];
    }

    public static function beforeSave(Model $record, array &$data, Request $request, bool $creating): void
    {
        if ($creating && empty($data['author_id'])) {
            $data['author_id'] = $request->user()->id;
        }
    }

    public static function extra(?Model $record): ?array
    {
        return $record ? ['preview_url' => $record->url(), 'scheduled' => $record->isScheduled(), 'published_at' => $record->published_at?->toIso8601String()] : null;
    }
}
