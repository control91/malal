<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CaseStudy;
use App\Models\Testimonial;
use App\Support\Images;
use App\Support\Present;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CaseStudyController extends Controller
{
    public function index(Request $request): Response
    {
        $all = CaseStudy::published()->ordered()->with('images')->get();
        $sector = (string) $request->query('secteur');

        return Inertia::render('Site/Cases', [
            'cases' => $all->when($sector, fn ($c) => $c->where('sector', $sector))->values()->map(fn ($c) => Present::caseCard($c) + [
                'client' => $c->publicClient(), 'year' => $c->year,
                'images' => $c->images->take(3)->map(fn ($i) => ['id' => $i->id, 'image' => Images::url($i->image), 'caption' => $i->caption])->all(),
            ]),
            'sectors' => $all->pluck('sector')->unique()->sort()->values(),
            'current_sector' => $sector,
            'testimonials' => Testimonial::published()->ordered()->limit(6)->get()->map(fn ($t) => Present::testimonial($t)),
        ])->with('seo', Seo::make('Réalisations', 'Projets menés et résultats obtenus avec nos clients.'));
    }

    public function show(string $slug): Response
    {
        $case = CaseStudy::published()->where('slug', $slug)->with(['services', 'images', 'testimonials' => fn ($q) => $q->published()])->firstOrFail();

        return Inertia::render('Site/Case', [
            'item' => Present::caseCard($case) + [
                'client' => $case->publicClient(), 'year' => $case->year,
                'body' => $case->body,
                'services' => $case->services->map(fn ($s) => ['name' => $s->name, 'url' => $s->url()])->all(),
                'images' => $case->images->map(fn ($i) => ['id' => $i->id, 'image' => Images::url($i->image), 'caption' => $i->caption])->all(),
                'testimonials' => $case->testimonials->map(fn ($t) => Present::testimonial($t))->all(),
            ],
        ])->with('seo', Seo::make($case->title, $case->summary, Images::url($case->cover), $case, jsonLd: [
            Seo::breadcrumb([['Accueil', '/'], ['Réalisations', route('cases.index')], [$case->title, $case->url()]]),
        ]));
    }
}
