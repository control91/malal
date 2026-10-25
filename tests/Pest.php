<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

function seedSite(): void
{
    test()->seed(SiteContentSeeder::class);
}

function staff(Role $role = Role::Viewer, array $attributes = []): User
{
    $user = new User(['email' => $attributes['email'] ?? "{$role->value}-".uniqid().'@test.local', 'first_name' => ucfirst($role->value)] + $attributes);
    $user->forceFill(['password' => 'Str0ng-pass-for-tests!', 'role' => $role, 'is_active' => $attributes['is_active'] ?? true])->save();

    return $user;
}

/** Encrypted anti-spam timestamp issued $secondsAgo seconds ago. */
function formToken(int $secondsAgo = 10): string
{
    return Crypt::encryptString((string) (time() - $secondsAgo));
}
