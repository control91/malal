<?php

namespace App\Models;

use App\Enums\Budget;
use App\Enums\InquiryKind;
use App\Enums\InquiryStatus;
use App\Enums\Timeline;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** Any request submitted on the website (contact, quote, demo…). */
class Inquiry extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['full_name'];

    protected $hidden = ['ip_address', 'user_agent'];

    protected function casts(): array
    {
        return [
            'kind' => InquiryKind::class, 'status' => InquiryStatus::class, 'budget' => Budget::class, 'timeline' => Timeline::class,
            'consent' => 'boolean', 'consent_at' => 'datetime', 'answered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Inquiry $inquiry) {
            if (! $inquiry->reference) {
                $prefix = match ($inquiry->kind) {
                    InquiryKind::Quote => 'DEV', InquiryKind::Demo => 'DEM', InquiryKind::Career => 'CAN',
                    InquiryKind::Partnership => 'PAR', InquiryKind::Support => 'SUP', default => 'CON',
                };
                $inquiry->reference = $prefix.'-'.now()->format('ym').'-'.Str::upper(Str::random(6));
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PricingPlan::class, 'pricing_plan_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->latest('created_at');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function adminUrl(): string
    {
        return route('admin.inquiries.show', $this);
    }

    public function __toString(): string
    {
        return "{$this->reference} · {$this->full_name}";
    }
}
