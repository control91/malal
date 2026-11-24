<?php

namespace App\Http\Middleware;

use App\Models\DailyVisitor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/** Counts successful HTML page loads of the public site (no cookie, aggregated per day). */
class TrackPageViews
{
    private const BOTS = '/bot|crawl|spider|slurp|preview|monitor|curl|wget|python|headless|lighthouse/i';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if (config('malal.analytics_enabled') && $this->shouldCount($request, $response)) {
            try {
                $this->record($request);
            } catch (\Throwable $e) {
                Log::warning('page view not recorded', ['error' => $e->getMessage()]);
            }
        }

        return $response;
    }

    private function shouldCount(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return false;
        }
        // Inertia client-side visits send X-Inertia: count them too, but skip partial reloads.
        if ($request->header('X-Inertia-Partial-Data') || $request->is('admin', 'admin/*', 'api/*', 'connexion', 'up', 'build/*', 'storage/*')) {
            return false;
        }
        if ($request->header('DNT') === '1' || $request->header('Sec-GPC') === '1' || $request->user()) {
            return false;
        }

        return ! preg_match(self::BOTS, (string) $request->userAgent());
    }

    private function record(Request $request): void
    {
        $today = today()->toDateString();
        $path = mb_substr('/'.ltrim($request->path(), '/'), 0, 255);
        DB::table('daily_page_views')->upsert([['date' => $today, 'path' => $path, 'views' => 1]], ['date', 'path'], ['views' => DB::raw('daily_page_views.views + 1')]);
        $visitor = substr(hash('sha256', config('app.key')."|$today|{$request->ip()}|{$request->userAgent()}"), 0, 32);
        DailyVisitor::insertOrIgnore(['date' => $today, 'visitor' => $visitor]);
    }
}
