<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('admin_authenticated')) {
            return redirect()->route('admin.login');
        }

        $username = Setting::get('admin.username');
        $passwordHash = Setting::get('admin.password');
        if (! is_string($username) || $username === '' || ! is_string($passwordHash) || $passwordHash === '') {
            return $this->logout($request);
        }

        $currentVersion = (string) Setting::get('admin.credentials_version', hash('sha256', $passwordHash));
        if (! hash_equals($currentVersion, (string) session('admin_credentials_version', ''))) {
            return $this->logout($request);
        }

        $timeoutSeconds = max(900, (int) Setting::get('security.admin_session_timeout', '120') * 60);
        $lastActivity = (int) session('admin_last_activity_at', 0);
        if ($lastActivity === 0 || now()->timestamp - $lastActivity > $timeoutSeconds) {
            return $this->logout($request);
        }

        session(['admin_last_activity_at' => now()->timestamp]);

        if (Setting::get('admin.must_change_credentials', '0') === '1'
            && ! $request->routeIs('admin.credentials.*')
            && ! $request->routeIs('admin.logout')) {
            return redirect()->route('admin.credentials.edit');
        }

        return $next($request);
    }

    private function logout(Request $request): Response
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
