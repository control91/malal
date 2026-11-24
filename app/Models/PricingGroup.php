<?php

namespace App\Models;

use App\Models\Concerns\Ordered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A family of plans compared side by side on the pricing page. */
class PricingGroup extends Model
{
    use Ordered;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function plans(): HasMany
    {
        return $this->hasMany(PricingPlan::class)->orderBy('order')->orderBy('id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(PricingOption::class)->orderBy('order')->orderBy('id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Rows [label, cells per plan] of the comparison table. */
    public function comparison(iterable $plans): array
    {
        $labels = [];
        $cells = [];
        foreach ($plans as $plan) {
            foreach ($plan->features as $feature) {
                $labels[$feature->label] = true;
                $cells[$plan->id][$feature->label] = ['value' => $feature->value, 'included' => $feature->included];
            }
        }

        return array_map(fn ($label) => [
            'label' => $label,
            'cells' => array_map(fn ($plan) => $cells[$plan->id][$label] ?? null, is_array($plans) ? $plans : iterator_to_array($plans)),
        ], array_keys($labels));
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
