<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Models\Concerns\Ordered;
use App\Models\Concerns\SanitizesRichText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A project reference — published only with the client's documented consent. */
class CaseStudy extends Model
{
    use Ordered, SanitizesRichText;

    protected $guarded = ['id'];

    protected array $richText = ['challenge', 'solution', 'results'];

    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class, 'show_client_name' => 'boolean', 'publication_consent' => 'boolean',
            'is_example' => 'boolean', 'noindex' => 'boolean', 'year' => 'integer',
        ];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(ServiceCategory::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(CaseStudyImage::class)->orderBy('order');
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Published)->where(fn ($q) => $q->where('publication_consent', true)->orWhere('is_example', true));
    }

    public function publicClient(): string
    {
        return $this->show_client_name && $this->publication_consent ? $this->client_name : '';
    }

    public function url(): string
    {
        return route('cases.show', $this->slug);
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
