<?php

namespace App\Services;

use App\Models\BlogArticle;
use App\Models\CaseStudy;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ServiceCategory;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Support\Images;
use App\Support\Present;

/** Resolves the data each section kind needs (one query per kind present on the page). */
final class SectionLoader
{
    public function load(Page $page): array
    {
        $sections = $page->sections()->where('is_active', true)->with('items')->get();
        $kinds = $sections->pluck('kind')->unique();
        $data = [];

        if ($kinds->intersect(['domains', 'marquee'])->isNotEmpty()) {
            $data['domains'] = ServiceCategory::where('is_published', true)->where('show_on_home', true)->ordered()->get()->map(fn ($c) => Present::category($c))->all();
            $data['marquee'] = $data['domains'];
        }
        if ($kinds->contains('solutions')) {
            $data['solutions'] = Product::published()->where('is_featured', true)->with(['category', 'features'])->ordered()->get()->map(fn ($p) => Present::productCard($p))->all();
        }
        if ($kinds->contains('news')) {
            $data['news'] = BlogArticle::published()->with('category')->latest('published_at')->limit(6)->get()->map(fn ($a) => Present::articleCard($a))->all();
        }
        if ($kinds->contains('team')) {
            $data['team'] = TeamMember::where('is_published', true)->ordered()->get()->map(fn ($m) => [
                'id' => $m->id, 'name' => $m->name, 'role' => $m->role, 'bio' => $m->bio, 'photo' => Images::url($m->photo), 'linkedin_url' => $m->linkedin_url,
            ])->all();
        }
        if ($kinds->contains('partners')) {
            $data['partners'] = Partner::where('is_published', true)->where('agreement_confirmed', true)->ordered()->get()->map(fn ($p) => [
                'id' => $p->id, 'name' => $p->name, 'url' => $p->url, 'logo' => Images::url($p->logo),
            ])->all();
        }
        if ($kinds->contains('testimonials')) {
            $data['testimonials'] = Testimonial::published()->ordered()->get()->map(fn ($t) => Present::testimonial($t))->all();
        }
        if ($kinds->contains('case_studies')) {
            $data['case_studies'] = CaseStudy::published()->ordered()->get()->map(fn ($c) => Present::caseCard($c))->all();
        }
        if ($kinds->contains('faq')) {
            $data['faq'] = Faq::where('is_published', true)->whereNull('service_category_id')->ordered()->get()->map(fn ($f) => Present::faq($f))->all();
        }

        return $sections->map(fn ($s) => [
            'id' => $s->id, 'kind' => $s->kind, 'theme' => $s->theme, 'anchor' => $s->anchor, 'eyebrow' => $s->eyebrow, 'title' => $s->title,
            'subtitle' => $s->subtitle, 'body' => $s->body, 'image' => Images::url($s->image), 'image_alt' => $s->image_alt,
            'cta_label' => $s->cta_label, 'cta_url' => $s->cta_url, 'cta2_label' => $s->cta2_label, 'cta2_url' => $s->cta2_url,
            'items' => $s->items->map(fn ($i) => $i->only(['id', 'icon', 'title', 'text', 'badge', 'progress', 'link_url', 'link_label']))->all(),
            'data' => isset($data[$s->kind]) ? array_slice($data[$s->kind], 0, $s->limit ?: null) : null,
        ])->all();
    }
}
