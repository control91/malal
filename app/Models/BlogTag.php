<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogTag extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(BlogArticle::class);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
