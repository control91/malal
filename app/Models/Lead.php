<?php

namespace App\Models;

use App\Enums\LeadPriority;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A person / organisation followed by the sales team (mini CRM). */
class Lead extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['full_name'];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class, 'priority' => LeadPriority::class, 'source' => LeadSource::class,
            'estimated_value' => 'decimal:2', 'next_follow_up_at' => 'datetime', 'converted_at' => 'datetime', 'is_client' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function interest(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class)->latest();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->latest('created_at');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function followUpOverdue(): bool
    {
        return $this->next_follow_up_at && $this->next_follow_up_at->isPast() && ! in_array($this->status, [LeadStatus::Won, LeadStatus::Lost], true);
    }

    public function __toString(): string
    {
        return $this->organization ? "{$this->full_name} ({$this->organization})" : $this->full_name;
    }
}
