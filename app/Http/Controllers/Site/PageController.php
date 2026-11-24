<?php

namespace App\Http\Controllers\Site;

use App\Enums\PageTemplate;
use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\JobOpening;
use App\Models\Page;
use App\Models\Partner;
use App\Models\ServiceCategory;
use App\Models\SiteSetting;
use App\Models\StatusComponent;
use App\Services\SectionLoader;
use App\Support\Images;
use App\Support\Present;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(private SectionLoader $sections) {}

    public function home(Request $request): Response
    {
        $page = $this->visiblePage($request, 'accueil');
        $site = SiteSetting::current();
        $seo = Seo::make($site->meta_title ?: $site->company_name, $site->meta_description ?: $site->tagline, model: $page,
            jsonLd: [Seo::organization($site), ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => $site->company_name, 'url' => config('app.url')]]);

        return Inertia::render('Site/Home', ['sections' => $page ? $this->sections->load($page) : []])->with('seo', $seo);
    }

    public function show(Request $request, string $slug): Response
    {
        $page = $slug === 'accueil' ? null : $this->visiblePage($request, $slug);
        abort_unless($page, 404);
        $seo = Seo::make($page->title, $page->intro, model: $page, jsonLd: [Seo::breadcrumb([['Accueil', '/'], [$page->title, $page->url()]])]);

        return Inertia::render('Site/Page', [
            'page' => [
                'title' => $page->title, 'eyebrow' => $page->eyebrow, 'intro' => $page->intro, 'body' => $page->body,
                'is_legal' => $page->template === PageTemplate::Legal, 'is_published' => $page->status->value === 'published',
                'updated_at' => $page->updated_at?->toIso8601String(),
            ],
            'sections' => $this->sections->load($page),
        ])->with('seo', $seo);
    }

    public function faq(): Response
    {
        $faqs = Faq::where('is_published', true)->ordered()->get();
        $groups = [['title' => 'Général', 'faqs' => $faqs->whereNull('service_category_id')->map(fn ($f) => Present::faq($f))->values()->all()]];
        foreach (ServiceCategory::where('is_published', true)->ordered()->get() as $category) {
            $items = $faqs->where('service_category_id', $category->id);
            if ($items->isNotEmpty()) {
                $groups[] = ['title' => $category->name, 'faqs' => $items->map(fn ($f) => Present::faq($f))->values()->all()];
            }
        }
        $jsonLd = $faqs->isEmpty() ? [] : [[
            '@context' => 'https://schema.org', '@type' => 'FAQPage',
            'mainEntity' => $faqs->map(fn ($f) => ['@type' => 'Question', 'name' => $f->question, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f->answer]])->values()->all(),
        ]];

        return Inertia::render('Site/Faq', ['groups' => array_values(array_filter($groups, fn ($g) => $g['faqs']))])
            ->with('seo', Seo::make('Questions fréquentes', 'Réponses aux questions les plus courantes sur nos services Cloud, SaaS et IA.', jsonLd: $jsonLd));
    }

    public function careers(): Response
    {
        $jobs = JobOpening::open()->latest()->get()->map(fn ($j) => [
            'title' => $j->title, 'slug' => $j->slug, 'location' => $j->location, 'remote_possible' => $j->remote_possible,
            'contract' => $j->contract->label(), 'department' => $j->department,
        ]);

        return Inertia::render('Site/Careers', ['jobs' => $jobs])
            ->with('seo', Seo::make('Carrières', "Rejoignez une équipe qui construit des services numériques pour l'Afrique."));
    }

    public function job(string $slug): Response
    {
        $job = JobOpening::open()->where('slug', $slug)->firstOrFail();
        $jsonLd = [array_filter([
            '@context' => 'https://schema.org', '@type' => 'JobPosting', 'title' => $job->title, 'description' => $job->description,
            'datePosted' => $job->created_at->toDateString(),
            'employmentType' => ['cdi' => 'FULL_TIME', 'cdd' => 'TEMPORARY', 'internship' => 'INTERN', 'freelance' => 'CONTRACTOR'][$job->contract->value],
            'jobLocation' => ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $job->location, 'addressCountry' => 'GN']],
            'validThrough' => $job->closes_on?->toDateString(),
        ])];

        return Inertia::render('Site/Job', ['job' => [
            'title' => $job->title, 'summary' => $job->summary, 'description' => $job->description, 'location' => $job->location,
            'remote_possible' => $job->remote_possible, 'contract' => $job->contract->label(), 'closes_on' => $job->closes_on?->toDateString(),
        ]])->with('seo', Seo::make($job->title, $job->summary, jsonLd: $jsonLd));
    }

    public function partners(): Response
    {
        $partners = Partner::where('is_published', true)->where('agreement_confirmed', true)->ordered()->get()->map(fn ($p) => [
            'id' => $p->id, 'name' => $p->name, 'kind' => $p->kind->label(), 'url' => $p->url, 'description' => $p->description, 'logo' => Images::url($p->logo),
        ]);

        return Inertia::render('Site/Partners', ['partners' => $partners])
            ->with('seo', Seo::make('Partenaires', 'Les organisations avec lesquelles nous collaborons.'));
    }

    public function status(): Response
    {
        $components = StatusComponent::where('is_published', true)->ordered()->get();
        $states = $components->pluck('state')->map->value;
        $overall = collect(['outage', 'degraded', 'maintenance'])->first(fn ($s) => $states->contains($s)) ?? 'operational';

        return Inertia::render('Site/Status', [
            'overall' => $overall,
            'updated_at' => $components->max('updated_at')?->toIso8601String(),
            'components' => $components->map(fn ($c) => ['name' => $c->name, 'description' => $c->description, 'state' => $c->state->value, 'state_label' => $c->state->label(), 'message' => $c->message]),
        ])->with('seo', Seo::make('Statut des services', 'État des services en production.'));
    }

    private function visiblePage(Request $request, string $slug): ?Page
    {
        $page = Page::where('slug', $slug)->first();
        if (! $page) {
            return null;
        }

        return $page->status->value === 'published' || $request->user() ? $page : null;
    }
}
