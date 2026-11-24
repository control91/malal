<?php

namespace App\Models;

use App\Enums\PublishStatus;
use App\Models\Concerns\SanitizesRichText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogArticle extends Model
{
    use SanitizesRichText;

    protected $guarded = ['id'];

    protected array $richText = ['body'];

    protected function casts(): array
    {
        return ['status' => PublishStatus::class, 'published_at' => 'datetime', 'is_featured' => 'boolean', 'noindex' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (BlogArticle $article) {
            if ($article->status === PublishStatus::Published && ! $article->published_at) {
                $article->published_at = now();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class);
    }

    /** Published and due: a future published_at means a scheduled article. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Published)->where('published_at', '<=', now());
    }

    public function isLive(): bool
    {
        return $this->status === PublishStatus::Published && $this->published_at && $this->published_at->isPast();
    }

    public function isScheduled(): bool
    {
        return $this->status === PublishStatus::Published && $this->published_at && $this->published_at->isFuture();
    }

    public function displayStatus(): string
    {
        return $this->isScheduled() ? 'Programmé' : $this->status->label();
    }

    public function readingTime(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->body)) / 220));
    }

    public function url(): string
    {
        return route('blog.show', $this->slug);
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
