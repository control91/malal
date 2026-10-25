<?php

namespace App\Models;

use App\Enums\Availability;
use App\Models\Concerns\Ordered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use Ordered;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['status' => Availability::class, 'is_featured' => 'boolean', 'is_published' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
