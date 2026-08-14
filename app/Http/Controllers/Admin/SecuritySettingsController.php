<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLoginLog;
use App\Models\LoginActivity;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class SecuritySettingsController extends Controller
{
    public function index(): Response
    {
        $recentLogins = LoginActivity::with('user:id,name,email')
            ->latest()
            ->take(30)
            ->get()
            ->map(fn ($a) => [
                'id'          => $a->id,
                'user'        => $a->user ? ['name' => $a->user->name, 'email' => $a->user->email] : null,
                'ip_address'  => $a->ip_address,
                'user_agent'  => $a->user_agent,
                'browser'     => $a->browser,
                'os'          => $a->os,
                'device_type' => $a->device_type,
                'action'      => $a->action ?? 'login',
                'is_current'  => (bool) $a->is_current,
                'created_at'  => $a->created_at->toISOString(),
            ]);

        $adminLogs = AdminLoginLog::latest()
            ->take(50)
            ->get()
            ->map(fn ($l) => [
                'id'               => $l->id,
                'admin_username'   => $l->admin_username,
                'action'           => $l->action,
                'ip_address'       => $l->ip_address,
                'browser'          => $l->browserLabel(),
                'os'               => $l->osLabel(),
                'status'           => $l->status,
                'login_at'         => $l->login_at?->toISOString(),
                'logout_at'        => $l->logout_at?->toISOString(),
                'duration_minutes' => $l->duration_minutes,
                'created_at'       => $l->created_at->toISOString(),
            ]);

        return Inertia::render('Admin/SecuritySettings', [
            'settings' => [
                'platform' => [
                    'maintenance_mode'      => Setting::get('site.maintenance_mode', '0') === '1',
                    'maintenance_message'   => Setting::get('site.maintenance_message', ''),
                    'api_rate_limit'        => (int) Setting::get('security.api_rate_limit', '60'),
                    'login_attempts_limit'  => (int) Setting::get('security.login_attempts_limit', '5'),
                    'lockout_duration'      => (int) Setting::get('security.lockout_duration', '30'),
                    'require_email_verify'  => Setting::get('security.require_email_verify', '1') === '1',
                    'allow_registration'    => Setting::get('security.allow_registration', '1') === '1',
                ],
                'password' => [
                    'min_length'       => (int) Setting::get('security.password_min_length', '4'),
                    'require_uppercase' => Setting::get('security.password_require_uppercase', '1') === '1',
                    'require_numbers'   => Setting::get('security.password_require_numbers', '1') === '1',
                    'require_special'   => Setting::get('security.password_require_special', '0') === '1',
                ],
                'admin' => [
                    'session_timeout' => (int) Setting::get('security.admin_session_timeout', '120'),
                    'username'        => Setting::get('admin.username', 'admin'),
                ],
                'ip_whitelist' => [
                    'enabled' => Setting::get('security.ip_whitelist_enabled', '0') === '1',
                    'ips'     => Setting::get('security.ip_whitelist', ''),
                ],
            ],
            'recent_logins' => $recentLogins,
            'admin_login_logs' => $adminLogs,
            'current_ip'    => request()->ip(),
        ]);
    }

    public function savePlatform(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'maintenance_mode'     => ['boolean'],
            'maintenance_message'  => ['nullable', 'string', 'max:500'],
            'api_rate_limit'       => ['required', 'integer', 'min:10', 'max:1000'],
            'login_attempts_limit' => ['required', 'integer', 'min:1', 'max:20'],
            'lockout_duration'     => ['required', 'integer', 'min:1', 'max:1440'],
            'require_email_verify' => ['boolean'],
            'allow_registration'   => ['boolean'],
        ]);

        Setting::set('site.maintenance_mode',       ($validated['maintenance_mode']    ?? false) ? '1' : '0');
        Setting::set('site.maintenance_message',    $validated['maintenance_message']  ?? '');
        Setting::set('security.api_rate_limit',     (string) $validated['api_rate_limit']);
        Setting::set('security.login_attempts_limit', (string) $validated['login_attempts_limit']);
        Setting::set('security.lockout_duration',   (string) $validated['lockout_duration']);
        Setting::set('security.require_email_verify', ($validated['require_email_verify'] ?? true) ? '1' : '0');
        Setting::set('security.allow_registration', ($validated['allow_registration']   ?? true) ? '1' : '0');

        return back()->with('success', 'Platform security settings saved.');
    }

    public function savePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'min_length'        => ['required', 'integer', 'min:4', 'max:32'],
            'require_uppercase' => ['boolean'],
            'require_numbers'   => ['boolean'],
            'require_special'   => ['boolean'],
        ]);

        Setting::set('security.password_min_length',        (string) $validated['min_length']);
        Setting::set('security.password_require_uppercase', ($validated['require_uppercase'] ?? false) ? '1' : '0');
        Setting::set('security.password_require_numbers',   ($validated['require_numbers']   ?? false) ? '1' : '0');
        Setting::set('security.password_require_special',   ($validated['require_special']   ?? false) ? '1' : '0');

        return back()->with('success', 'Password policy saved.');
    }

    public function saveAdmin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username'         => ['required', 'string', 'min:3', 'max:50'],
            'current_password' => ['required', 'string'],
            'new_password'     => ['nullable', 'string', 'min:4', 'confirmed'],
            'session_timeout'  => ['required', 'integer', 'min:15', 'max:1440'],
        ]);

        $storedPassword = Setting::get('admin.password', '');
        $currentValid = $storedPassword
            ? Hash::check($validated['current_password'], $storedPassword)
            : ($validated['current_password'] === '1234'); // fallback for dev default

        if (!$currentValid) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        Setting::set('admin.username', $validated['username']);
        Setting::set('security.admin_session_timeout', (string) $validated['session_timeout']);

        if (!empty($validated['new_password'])) {
            Setting::set('admin.password', Hash::make($validated['new_password']));
        }

        return back()->with('success', 'Admin credentials updated.');
    }

    public function saveIpWhitelist(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['boolean'],
            'ips'     => ['nullable', 'string', 'max:5000'],
        ]);

        // Safety: if enabling, make sure current IP is in the list
        $ipList = collect(preg_split('/[\r\n,]+/', $validated['ips'] ?? ''))
            ->map(fn ($ip) => trim($ip))
            ->filter(fn ($ip) => $ip !== '')
            ->unique()
            ->values();

        if (($validated['enabled'] ?? false) && $ipList->isNotEmpty()) {
            if (!$ipList->contains(request()->ip())) {
                return back()->withErrors(['ips' => 'Your current IP (' . request()->ip() . ') must be in the whitelist before enabling, or you will lock yourself out.']);
            }
        }

        Setting::set('security.ip_whitelist_enabled', ($validated['enabled'] ?? false) ? '1' : '0');
        Setting::set('security.ip_whitelist', $ipList->join("\n"));

        return back()->with('success', 'IP whitelist saved.');
    }
}
