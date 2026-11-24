<?php

namespace App\Http\Controllers\Site;

use App\Enums\Availability;
use App\Http\Controllers\Controller;
use App\Models\CaseStudy;
use App\Models\Product;
use App\Models\ServiceCategory;
use App\Models\SiteSetting;
use App\Support\Images;
use App\Support\Present;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class ExpertiseController extends Controller
{
    public function show(string $slug): Response
    {
        $category = ServiceCategory::where('slug', $slug)->where('is_published', true)->firstOrFail();
        $services = $category->services()->where('is_published', true)->get();
        $groups = collect(Availability::cases())->map(fn ($status) => [
            'status' => $status->value,
            'services' => $services->where('status', $status)->values()->map(fn ($s) => [
                'id' => $s->id, 'name' => $s->name, 'icon' => $s->icon, 'summary' => $s->summary,
                'details' => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $s->details)))),
            ])->all(),
        ])->filter(fn ($g) => $g['services'])->values();

        $seo = Seo::make($category->name, $category->tagline ?: $category->summary, model: $category, jsonLd: [
            Seo::breadcrumb([['Accueil', '/'], [$category->name, $category->url()]]),
            ['@context' => 'https://schema.org', '@type' => 'Service', 'name' => $category->name, 'description' => $category->summary,
                'provider' => ['@type' => 'Organization', 'name' => SiteSetting::current()->company_name], 'areaServed' => 'Africa'],
        ]);

        return Inertia::render('Site/Expertise', [
            'category' => Present::category($category) + [
                'hero_title' => $category->hero_title, 'hero_subtitle' => $category->hero_subtitle, 'description' => $category->description,
                'image' => Images::url($category->image), 'show_hosting_disclosure' => $category->show_hosting_disclosure, 'demo_space_enabled' => $category->demo_space_enabled,
            ],
            'groups' => $groups,
            'products' => Product::published()->where('service_category_id', $category->id)->with(['category', 'features'])->ordered()->get()->map(fn ($p) => Present::productCard($p)),
            'faqs' => $category->faqs()->where('is_published', true)->get()->map(fn ($f) => Present::faq($f)),
            'cases' => CaseStudy::published()->whereHas('services', fn ($q) => $q->whereKey($category->id))->limit(3)->get()->map(fn ($c) => Present::caseCard($c)),
        ])->with('seo', $seo);
    }
}
