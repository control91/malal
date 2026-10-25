<?php

namespace App\Models;

use App\Models\Concerns\Ordered;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use Ordered;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_leadership' => 'boolean', 'is_published' => 'boolean'];
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
