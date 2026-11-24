<?php

namespace App\Models;

use App\Enums\ContractType;
use App\Models\Concerns\SanitizesRichText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class JobOpening extends Model
{
    use SanitizesRichText;

    protected $guarded = ['id'];

    protected array $richText = ['description'];

    protected function casts(): array
    {
        return ['contract' => ContractType::class, 'remote_possible' => 'boolean', 'is_open' => 'boolean', 'closes_on' => 'date'];
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_open', true)->where(fn ($q) => $q->whereNull('closes_on')->orWhereDate('closes_on', '>=', today()));
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
