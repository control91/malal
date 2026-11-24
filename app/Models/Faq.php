<?php

namespace App\Models;

use App\Models\Concerns\Ordered;
use App\Models\Concerns\SanitizesRichText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends Model
{
    use Ordered, SanitizesRichText;

    protected $guarded = ['id'];

    protected array $richText = ['answer'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function __toString(): string
    {
        return $this->question;
    }
}
