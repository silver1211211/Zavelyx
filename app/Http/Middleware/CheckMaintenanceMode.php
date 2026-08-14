<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Setting::get('site.maintenance_mode', '0') !== '1') {
            return $next($request);
        }

        // Always allow admin panel and admin auth routes
        if ($request->is('admin*')) {
            return $next($request);
        }

        // Always allow the home page so users see maintenance info
        if ($request->is('/') || $request->routeIs('home')) {
            return $next($request);
        }

        $message = Setting::get('site.maintenance_message', 'We are performing scheduled maintenance. We\'ll be back shortly.');

        if ($request->header('X-Inertia')) {
            return Inertia::render('Maintenance', ['message' => $message])
                ->toResponse($request)
                ->setStatusCode(503);
        }

        return response()->view('maintenance', ['message' => $message], 503);
    }
}
