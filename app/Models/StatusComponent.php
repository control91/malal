<?php

namespace App\Models;

use App\Enums\StatusState;
use App\Models\Concerns\Ordered;
use Illuminate\Database\Eloquent\Model;

class StatusComponent extends Model
{
    use Ordered;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['state' => StatusState::class, 'is_published' => 'boolean'];
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
