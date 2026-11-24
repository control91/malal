<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BlogArticle;
use App\Models\Product;
use App\Models\ServiceCategory;
use App\Models\StatusComponent;
use Illuminate\Http\JsonResponse;

/** Read-only public API v1 (documented in docs/ARCHITECTURE.md). */
class PublicApiController extends Controller
{
    public function expertises(): JsonResponse
    {
        return response()->json(['data' => ServiceCategory::where('is_published', true)->ordered()->with(['services' => fn ($q) => $q->where('is_published', true)])->get()
            ->map(fn ($c) => [
                'name' => $c->name, 'slug' => $c->slug, 'tagline' => $c->tagline, 'summary' => $c->summary, 'url' => $c->url(),
                'services' => $c->services->map(fn ($s) => ['name' => $s->name, 'slug' => $s->slug, 'summary' => $s->summary, 'status' => $s->status->value]),
            ])]);
    }

    public function solutions(): JsonResponse
    {
        return response()->json(Product::published()->with(['category', 'features'])->ordered()->paginate(20)->through(fn ($p) => $this->product($p)));
    }

    public function solution(string $slug): JsonResponse
    {
        return response()->json(['data' => $this->product(Product::published()->with(['category', 'features'])->where('slug', $slug)->firstOrFail())]);
    }

    public function articles(): JsonResponse
    {
        return response()->json(BlogArticle::published()->with('category')->latest('published_at')->paginate(20)->through(fn ($a) => [
            'title' => $a->title, 'slug' => $a->slug, 'excerpt' => $a->excerpt, 'category' => $a->category?->slug,
            'published_at' => $a->published_at?->toIso8601String(), 'url' => $a->url(),
        ]));
    }

    public function status(): JsonResponse
    {
        return response()->json(['data' => StatusComponent::where('is_published', true)->ordered()->get()
            ->map(fn ($c) => ['name' => $c->name, 'state' => $c->state->value, 'message' => $c->message, 'updated_at' => $c->updated_at?->toIso8601String()])]);
    }

    private function product(Product $p): array
    {
        return [
            'name' => $p->name, 'slug' => $p->slug, 'tagline' => $p->tagline, 'summary' => $p->summary, 'status' => $p->status->value,
            'category' => $p->category?->slug, 'url' => $p->url(), 'features' => $p->features->map->only(['title', 'description']),
        ];
    }
}
