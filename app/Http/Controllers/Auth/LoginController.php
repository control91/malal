<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', ['status' => session('status')]);
    }

    public function store(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $key = Str::lower($credentials['email']).'|'.$request->ip();

        // Brute-force protection: N failures per (email, IP) lock the pair for a while.
        if (RateLimiter::tooManyAttempts($key, config('malal.login_max_attempts'))) {
            event(new Lockout($request));
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            throw ValidationException::withMessages(['email' => "Trop de tentatives. Accès bloqué pendant {$minutes} minute(s)."]);
        }

        if (! Auth::attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($key, config('malal.login_decay_seconds'));
            throw ValidationException::withMessages(['email' => 'Identifiants incorrects. Après plusieurs échecs, l\'accès est temporairement bloqué.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        // Full page load: the back-office receives its own route list and assets.
        return Inertia::location(redirect()->intended(route('admin.dashboard'))->getTargetUrl());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
