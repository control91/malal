<?php

namespace App\Models;

use App\Enums\PageTemplate;
use App\Enums\PublishStatus;
use App\Models\Concerns\SanitizesRichText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use SanitizesRichText;

    protected $guarded = ['id'];

    protected array $richText = ['body'];

    protected function casts(): array
    {
        return ['template' => PageTemplate::class, 'status' => PublishStatus::class, 'is_system' => 'boolean', 'noindex' => 'boolean'];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('order')->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Published);
    }

    public function url(): string
    {
        return $this->slug === 'accueil' ? route('home') : route('page', $this->slug);
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
