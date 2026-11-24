<?php

namespace App\Support;

use App\Models\BlogArticle;
use App\Models\CaseStudy;
use App\Models\Faq;
use App\Models\PricingPlan;
use App\Models\Product;
use App\Models\ServiceCategory;
use App\Models\Testimonial;

/** Serialises models into the minimal arrays the Vue pages need (nothing private leaks). */
final class Present
{
    public static function category(ServiceCategory $c): array
    {
        return ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug, 'icon' => $c->icon, 'tagline' => $c->tagline, 'summary' => $c->summary, 'url' => $c->url()];
    }

    public static function productCard(Product $p): array
    {
        return [
            'id' => $p->id, 'name' => $p->name, 'slug' => $p->slug, 'icon' => $p->icon, 'tagline' => $p->tagline, 'summary' => $p->summary,
            'status' => $p->status->value, 'is_example' => $p->is_example, 'logo' => Images::url($p->logo), 'url' => $p->url(),
            'category' => $p->relationLoaded('category') && $p->category ? $p->category->name : null,
            'features' => $p->relationLoaded('features') ? $p->features->take(3)->pluck('title')->all() : [],
        ];
    }

    public static function articleCard(BlogArticle $a): array
    {
        return [
            'id' => $a->id, 'title' => $a->title, 'slug' => $a->slug, 'excerpt' => $a->excerpt, 'url' => $a->url(),
            'cover' => Images::url($a->cover), 'cover_alt' => $a->cover_alt, 'published_at' => $a->published_at?->toIso8601String(),
            'reading_time' => $a->readingTime(),
            'category' => $a->relationLoaded('category') && $a->category ? ['name' => $a->category->name, 'url' => route('blog.category', $a->category->slug)] : null,
        ];
    }

    public static function caseCard(CaseStudy $c): array
    {
        return [
            'id' => $c->id, 'title' => $c->title, 'slug' => $c->slug, 'sector' => $c->sector, 'summary' => $c->summary,
            'cover' => Images::url($c->cover), 'is_example' => $c->is_example, 'url' => $c->url(),
            'technologies' => array_values(array_filter(array_map('trim', explode(',', $c->technologies)))),
        ];
    }

    public static function faq(Faq $f): array
    {
        return ['id' => $f->id, 'question' => $f->question, 'answer' => $f->answer];
    }

    public static function testimonial(Testimonial $t): array
    {
        return ['id' => $t->id, 'author_name' => $t->author_name, 'author_role' => $t->author_role, 'organization' => $t->organization, 'quote' => $t->quote, 'photo' => Images::url($t->photo)];
    }

    public static function plan(PricingPlan $p): array
    {
        return [
            'id' => $p->id, 'name' => $p->name, 'description' => $p->description, 'currency' => $p->currency->value,
            'price_monthly' => $p->price_monthly !== null ? (float) $p->price_monthly : null,
            'price_yearly' => $p->price_yearly !== null ? (float) $p->price_yearly : null,
            'yearly_monthly' => $p->yearlyMonthlyEquivalent(), 'saving' => $p->yearlySavingPercent(),
            'price_on_request' => $p->price_on_request, 'price_note' => $p->price_note, 'status' => $p->status->value,
            'is_highlighted' => $p->is_highlighted, 'cta_label' => $p->cta_label,
            'cta_url' => $p->cta_url ?: route('quote', ['offre' => $p->id]),
            'features' => $p->features->map(fn ($f) => ['label' => $f->label, 'value' => $f->value, 'included' => $f->included])->all(),
        ];
    }
}
