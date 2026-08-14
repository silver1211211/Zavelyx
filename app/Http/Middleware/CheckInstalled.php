<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip the check for the installer itself to avoid redirect loops
        if ($request->is('install.php') || str_starts_with($request->path(), '_debugbar')) {
            return $next($request);
        }

        try {
            $lockFile = storage_path('installed');
        } catch (\Throwable) {
            return redirect('/install.php');
        }

        if (! file_exists($lockFile)) {
            return redirect('/install.php');
        }

        return $next($request);
    }
}
