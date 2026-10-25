<?php

namespace App\Models;

use App\Enums\Availability;
use App\Enums\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingPlan extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'currency' => Currency::class, 'status' => Availability::class, 'price_monthly' => 'decimal:2', 'price_yearly' => 'decimal:2',
            'price_on_request' => 'boolean', 'is_highlighted' => 'boolean', 'is_published' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(PricingGroup::class, 'pricing_group_id');
    }

    public function features(): HasMany
    {
        return $this->hasMany(PricingFeature::class)->orderBy('order')->orderBy('id');
    }

    public function yearlyMonthlyEquivalent(): ?int
    {
        return $this->price_yearly === null ? null : (int) round((float) $this->price_yearly / 12);
    }

    public function yearlySavingPercent(): ?int
    {
        if (! $this->price_monthly || ! $this->price_yearly) {
            return null;
        }
        $full = (float) $this->price_monthly * 12;

        return $full > (float) $this->price_yearly ? (int) round((1 - (float) $this->price_yearly / $full) * 100) : null;
    }

    public function __toString(): string
    {
        return ($this->group?->name ? $this->group->name.' · ' : '').$this->name;
    }
}
