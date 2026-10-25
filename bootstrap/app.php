<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RestrictAdminIps;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TrackPageViews;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Csp\AddCspHeaders;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '127.0.0.1'), headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_HOST);
        $middleware->prepend(SecurityHeaders::class);
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddCspHeaders::class,
            TrackPageViews::class,
        ]);
        $middleware->alias(['admin.ip' => RestrictAdminIps::class, 'active' => EnsureUserIsActive::class]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Branded error pages (Inertia) for the usual HTTP errors outside local debug.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();
            if (in_array($status, [403, 404, 419, 429, 500, 503], true) && (! app()->hasDebugModeEnabled() || app()->runningUnitTests())) {
                if ($status === 419) {
                    return back()->with('error', 'Votre session a expiré. Merci de réessayer.');
                }
                if ($request->expectsJson() && ! $request->header('X-Inertia')) {
                    return $response;
                }

                return Inertia::render('Error', ['status' => $status])->toResponse($request)->setStatusCode($status);
            }

            return $response;
        });
    })->create();
