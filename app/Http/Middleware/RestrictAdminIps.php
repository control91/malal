<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/** Optional IP allow-list for the back-office (ADMIN_ALLOWED_IPS, comma separated IPs/CIDRs). */
class RestrictAdminIps
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = config('malal.admin_allowed_ips');
        if ($allowed && ! IpUtils::checkIp((string) $request->ip(), $allowed)) {
            logger()->warning('Back-office access denied', ['ip' => $request->ip(), 'path' => $request->path()]);
            abort(403);
        }

        return $next($request);
    }
}
