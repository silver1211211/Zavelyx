<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminIpWhitelist
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Setting::get('security.ip_whitelist_enabled', '0') !== '1') {
            return $next($request);
        }

        $raw = Setting::get('security.ip_whitelist', '');
        $allowed = collect(preg_split('/[\r\n,]+/', $raw))
            ->map(fn ($ip) => trim($ip))
            ->filter()
            ->values();

        if ($allowed->isEmpty() || $allowed->contains($request->ip())) {
            return $next($request);
        }

        abort(403, 'Access denied: your IP address is not whitelisted.');
    }
}
