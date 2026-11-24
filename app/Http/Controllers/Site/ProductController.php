<?php

namespace App\Http\Controllers\Site;

use App\Enums\Availability;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ServiceCategory;
use App\Models\SiteSetting;
use App\Support\Images;
use App\Support\Present;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    private const AI_CATEGORY = 'intelligence-artificielle';

    public function index(Request $request): Response
    {
        $filters = [
            'q' => mb_substr(trim((string) $request->query('q')), 0, 100),
            'domaine' => (string) $request->query('domaine'),
            'statut' => in_array($request->query('statut'), Availability::values(), true) ? $request->query('statut') : '',
        ];
        $products = Product::published()->with(['category', 'features'])
            ->when($filters['q'], fn ($q, $term) => $q->where(fn ($w) => $w->whereLike('name', "%$term%")->orWhereLike('tagline', "%$term%")->orWhereLike('summary', "%$term%")
                ->orWhereHas('features', fn ($f) => $f->whereLike('title', "%$term%"))))
            ->when($filters['domaine'], fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when($filters['statut'], fn ($q, $status) => $q->where('status', $status))
            ->ordered()->paginate(12)->withQueryString()->through(fn ($p) => Present::productCard($p));

        return Inertia::render('Site/Catalog', [
            'products' => $products,
            'filters' => $filters,
            'categories' => ServiceCategory::where('is_published', true)->whereHas('products', fn ($q) => $q->where('is_published', true))->ordered()->get(['name', 'slug']),
            'statuses' => Availability::options(),
        ])->with('seo', Seo::make('Catalogue des solutions', "Logiciels SaaS, services Cloud et solutions d'IA : découvrez notre catalogue."));
    }

    /** Showcase of the AI applications: the published solutions of the AI domain, with their screenshots. */
    public function ai(): Response
    {
        $category = ServiceCategory::where('slug', self::AI_CATEGORY)->where('is_published', true)->first();
        $products = $category
            ? Product::published()->where('service_category_id', $category->id)->with(['category', 'features', 'screenshots'])->ordered()->get()
            : collect();

        return Inertia::render('Site/Ai', [
            'category' => $category ? ['name' => $category->name, 'url' => $category->url()] : null,
            'products' => $products->map(fn ($p) => Present::productCard($p) + [
                'demo_enabled' => $p->demo_enabled, 'external_url' => $p->external_url,
                'all_features' => $p->features->take(6)->pluck('title')->all(),
                'screenshots' => $p->screenshots->take(3)->map(fn ($s) => ['id' => $s->id, 'image' => Images::url($s->image), 'caption' => $s->caption])->all(),
            ]),
        ])->with('seo', Seo::make('Intelligence Artificielle', "Nos applications d'Intelligence Artificielle : assistants, automatisation et API, avec des exemples d'utilisation.",
            jsonLd: [Seo::breadcrumb([['Accueil', '/'], ['IA', route('ai')]])]));
    }

    public function show(string $slug): Response
    {
        $product = Product::published()->where('slug', $slug)->with(['category', 'features', 'screenshots'])->firstOrFail();
        $groups = $product->show_pricing
            ? $product->pricingGroups()->where('is_published', true)->with(['plans' => fn ($q) => $q->where('is_published', true)->with('features')])->get()
                ->map(fn ($g) => ['name' => $g->name, 'description' => $g->description, 'plans' => $g->plans->map(fn ($p) => Present::plan($p))->all()])
            : [];
        $related = Product::published()->where('service_category_id', $product->service_category_id)->whereKeyNot($product->id)->with(['category', 'features'])->limit(3)->get();
        $seo = Seo::make($product->name, $product->tagline, Images::url($product->logo), $product, jsonLd: [
            Seo::breadcrumb([['Accueil', '/'], ['Solutions', route('solutions.index')], [$product->name, $product->url()]]),
            array_filter(['@context' => 'https://schema.org', '@type' => 'SoftwareApplication', 'name' => $product->name, 'description' => $product->summary,
                'applicationCategory' => 'BusinessApplication', 'operatingSystem' => 'Web', 'publisher' => ['@type' => 'Organization', 'name' => SiteSetting::current()->company_name],
                'image' => Seo::absolute(Images::url($product->logo))]),
        ]);

        return Inertia::render('Site/Product', [
            'product' => Present::productCard($product) + [
                'description' => $product->description, 'demo_enabled' => $product->demo_enabled,
                'external_url' => $product->external_url, 'category_url' => $product->category->url(),
                'all_features' => $product->features->map->only(['id', 'icon', 'title', 'description'])->all(),
                'screenshots' => $product->screenshots->map(fn ($s) => ['id' => $s->id, 'image' => Images::url($s->image), 'caption' => $s->caption])->all(),
            ],
            'pricingGroups' => $groups,
            'related' => $related->map(fn ($p) => Present::productCard($p)),
        ])->with('seo', $seo);
    }
}
