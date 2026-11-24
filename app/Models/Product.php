<?php

namespace App\Models;

use App\Enums\Availability;
use App\Models\Concerns\Ordered;
use App\Models\Concerns\SanitizesRichText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One of the company's own solutions (SaaS, AI product, cloud offer). */
class Product extends Model
{
    use Ordered, SanitizesRichText;

    protected $guarded = ['id'];

    protected array $richText = ['description'];

    protected function casts(): array
    {
        return [
            'status' => Availability::class, 'is_example' => 'boolean', 'demo_enabled' => 'boolean', 'show_pricing' => 'boolean',
            'is_featured' => 'boolean', 'is_published' => 'boolean', 'noindex' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function features(): HasMany
    {
        return $this->hasMany(ProductFeature::class)->orderBy('order')->orderBy('id');
    }

    public function screenshots(): HasMany
    {
        return $this->hasMany(ProductScreenshot::class)->orderBy('order')->orderBy('id');
    }

    public function pricingGroups(): HasMany
    {
        return $this->hasMany(PricingGroup::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->whereHas('category', fn ($q) => $q->where('is_published', true));
    }

    public function url(): string
    {
        return route('solutions.show', $this->slug);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
