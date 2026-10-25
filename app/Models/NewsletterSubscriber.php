<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['is_active'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return ['consent_at' => 'datetime', 'confirmed_at' => 'datetime', 'unsubscribed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $subscriber) => $subscriber->token ??= Str::random(48));
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->confirmed_at !== null && $this->unsubscribed_at === null;
    }

    public function __toString(): string
    {
        return $this->email;
    }
}
