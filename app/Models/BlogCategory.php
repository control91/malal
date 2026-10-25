<?php

namespace App\Models;

use App\Models\Concerns\Ordered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCategory extends Model
{
    use Ordered;

    public $timestamps = false;

    protected $guarded = ['id'];

    public function articles(): HasMany
    {
        return $this->hasMany(BlogArticle::class);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
