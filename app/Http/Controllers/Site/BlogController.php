<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\BlogArticle;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\SiteSetting;
use App\Support\Images;
use App\Support\Present;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->listing($request, BlogArticle::published(), 'Actualités et ressources', "Analyses, guides et actualités sur le Cloud, le SaaS et l'IA en Afrique.");
    }

    public function category(Request $request, string $slug): Response
    {
        $category = BlogCategory::where('slug', $slug)->firstOrFail();

        return $this->listing($request, BlogArticle::published()->where('blog_category_id', $category->id), $category->name, (string) $category->description, ['current_category' => $category->slug]);
    }

    public function tag(Request $request, string $slug): Response
    {
        $tag = BlogTag::where('slug', $slug)->firstOrFail();

        return $this->listing($request, BlogArticle::published()->whereHas('tags', fn ($q) => $q->whereKey($tag->id)), "#{$tag->name}", "Articles étiquetés {$tag->name}.", ['current_tag' => $tag->slug]);
    }

    public function show(Request $request, string $slug): Response
    {
        $query = $request->user() ? BlogArticle::query() : BlogArticle::published();
        $article = $query->where('slug', $slug)->with(['category', 'author', 'tags'])->firstOrFail();
        $site = SiteSetting::current();
        $related = BlogArticle::published()->whereKeyNot($article->id)
            ->when($article->blog_category_id, fn ($q) => $q->where('blog_category_id', $article->blog_category_id))
            ->with('category')->latest('published_at')->limit(3)->get();

        $seo = Seo::make($article->title, $article->excerpt, Images::url($article->cover), $article, 'article', ! $article->isLive(), [
            Seo::breadcrumb([['Accueil', '/'], ['Blog', route('blog.index')], [$article->title, $article->url()]]),
            array_filter([
                '@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $article->title, 'description' => $article->excerpt,
                'datePublished' => $article->published_at?->toIso8601String(), 'dateModified' => $article->updated_at->toIso8601String(),
                'author' => $article->author ? ['@type' => 'Person', 'name' => (string) $article->author] : ['@type' => 'Organization', 'name' => $site->company_name],
                'publisher' => ['@type' => 'Organization', 'name' => $site->company_name], 'mainEntityOfPage' => Seo::absolute($article->url()),
                'image' => Seo::absolute(Images::url($article->cover)),
            ]),
        ], $article->published_at?->toIso8601String());

        return Inertia::render('Site/Article', [
            'article' => Present::articleCard($article) + [
                'body' => $article->body,
                'status' => $article->displayStatus(),
                'preview' => ! $article->isLive(),
                'author' => $article->author ? ['name' => (string) $article->author, 'avatar' => Images::url($article->author->avatar)] : null,
                'tags' => $article->tags->map(fn ($t) => ['name' => $t->name, 'url' => route('blog.tag', $t->slug)])->all(),
            ],
            'related' => $related->map(fn ($a) => Present::articleCard($a)),
        ])->with('seo', $seo);
    }

    private function listing(Request $request, Builder $query, string $title, string $description, array $extra = []): Response
    {
        $q = mb_substr(trim((string) $request->query('q')), 0, 100);
        $articles = $query->with('category')
            ->when($q, fn ($b) => $b->where(fn ($w) => $w->whereLike('title', "%$q%")->orWhereLike('excerpt', "%$q%")->orWhereLike('body', "%$q%")))
            ->latest('published_at')->paginate(9)->withQueryString()->through(fn ($a) => Present::articleCard($a));

        return Inertia::render('Site/Blog', [
            'articles' => $articles,
            'heading' => $title,
            'description' => $description,
            'q' => $q,
            'categories' => BlogCategory::whereHas('articles')->ordered()->get(['name', 'slug']),
            ...$extra,
        ])->with('seo', Seo::make($title, $description));
    }
}
