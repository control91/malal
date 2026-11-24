<?php

namespace App\Models;

use App\Enums\PartnerKind;
use App\Models\Concerns\Ordered;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use Ordered;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['kind' => PartnerKind::class, 'agreement_confirmed' => 'boolean', 'is_published' => 'boolean'];
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
