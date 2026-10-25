<?php

namespace App\Providers;

use App\Enums\AuditAction;
use App\Models\User;
use App\Support\Audit;
use App\Support\Capabilities;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Password::defaults(fn () => Password::min(12)->uncompromised(3)->when(app()->runningUnitTests(), fn ($rule) => Password::min(12)));

        // Abilities of the form "<area>.<view|edit>" are resolved from the user's role.
        Gate::before(function (User $user, string $ability) {
            if (str_contains($ability, '.') && array_key_exists(strstr($ability, '.', true), Capabilities::AREAS)) {
                return Capabilities::has($user, $ability);
            }

            return null;
        });

        RateLimiter::for('public-forms', fn (Request $request) => Limit::perSecond(config('malal.form_rate_limit'), config('malal.form_rate_window'))
            ->by('forms|'.$request->ip())
            ->response(fn () => back()->withInput()->with('error', 'Trop de demandes envoyées depuis votre connexion. Merci de réessayer dans quelques minutes.')));

        Vite::useCspNonce(app('csp-nonce'));

        Event::listen(Login::class, fn (Login $event) => tap($event->user, function ($user) {
            $user->forceFill(['last_login_at' => now()])->saveQuietly();
            Audit::record(AuditAction::Login, $user, actor: $user);
        }));
        Event::listen(Logout::class, fn (Logout $event) => $event->user && Audit::record(AuditAction::Logout, $event->user, actor: $event->user));
        Event::listen(Failed::class, function (Failed $event) {
            $email = mb_substr((string) ($event->credentials['email'] ?? ''), 0, 120);
            Audit::record(AuditAction::LoginFailed, repr: $email, changes: ['email' => $email]);
        });
    }
}
