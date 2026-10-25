<?php

namespace App\Models;

use App\Models\Concerns\Ordered;
use App\Models\Concerns\SanitizesRichText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A domain of expertise (Cloud, SaaS, IA…); each one has its own public page. */
class ServiceCategory extends Model
{
    use Ordered, SanitizesRichText;

    protected $guarded = ['id'];

    protected array $richText = ['description'];

    protected function casts(): array
    {
        return [
            'show_hosting_disclosure' => 'boolean', 'demo_space_enabled' => 'boolean',
            'is_published' => 'boolean', 'show_on_home' => 'boolean', 'noindex' => 'boolean',
        ];
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->orderBy('order')->orderBy('id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->orderBy('order');
    }

    public function caseStudies(): BelongsToMany
    {
        return $this->belongsToMany(CaseStudy::class);
    }

    public function url(): string
    {
        return route('expertise', $this->slug);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
