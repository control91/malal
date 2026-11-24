<?php

namespace App\Models;

use App\Enums\Role;
use App\Support\Capabilities;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Central identity for the back-office today and, later, for the client portal
 * (see docs/ARCHITECTURE.md — the same table can back an OIDC provider).
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = ['id', 'password', 'remember_token'];

    protected $hidden = ['password', 'remember_token'];

    protected $appends = ['display_name'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (User $user) => $user->email = strtolower(trim($user->email)));
    }

    public function getDisplayNameAttribute(): string
    {
        $name = trim("{$this->first_name} {$this->last_name}");

        return $name !== '' ? $name : strstr($this->email, '@', true);
    }

    public function isSuperadmin(): bool
    {
        return $this->role === Role::Superadmin;
    }

    /** @return array<int, string> */
    public function capabilities(): array
    {
        return Capabilities::forUser($this);
    }

    public function canDo(string $capability): bool
    {
        return Capabilities::has($this, $capability);
    }

    public function adminNotifications(): HasMany
    {
        return $this->hasMany(AdminNotification::class)->latest('created_at');
    }

    public function __toString(): string
    {
        return $this->display_name;
    }
}
