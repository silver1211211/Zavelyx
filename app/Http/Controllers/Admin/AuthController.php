<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLoginLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthController extends Controller
{
    public function showLogin(): Response|RedirectResponse
    {
        if (session('admin_authenticated')) {
            return redirect()->route(
                Setting::get('admin.must_change_credentials', '0') === '1'
                    ? 'admin.credentials.edit'
                    : 'admin.dashboard'
            );
        }

        return Inertia::render('Admin/Login');
    }

    public function login(Request $request): RedirectResponse|SymfonyResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $key = $this->throttleKey($request);
        $maxAttempts = max(1, (int) Setting::get('security.login_attempts_limit', '5'));
        $lockoutSeconds = max(60, (int) Setting::get('security.lockout_duration', '30') * 60);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'username' => "Too many login attempts. Try again in {$seconds} seconds.",
            ]);
        }

        $storedUsername = Setting::get('admin.username');
        $storedPassword = Setting::get('admin.password');

        $usernameMatch = is_string($storedUsername)
            && $storedUsername !== ''
            && hash_equals($storedUsername, $request->string('username')->toString());
        $passwordMatch = false;

        if (is_string($storedPassword) && $storedPassword !== '') {
            try {
                $passwordMatch = Hash::check($request->string('password')->toString(), $storedPassword);
            } catch (\Throwable) {
                $passwordMatch = false;
            }
        }

        if ($usernameMatch && $passwordMatch) {
            RateLimiter::clear($key);
            $request->session()->regenerate();
            $request->session()->put([
                'admin_authenticated' => true,
                'admin_username' => $storedUsername,
                'admin_credentials_version' => $this->credentialsVersion($storedPassword),
                'admin_last_activity_at' => now()->timestamp,
            ]);
            $request->session()->save();

            AdminLoginLog::recordLogin($storedUsername, 'success');

            $destination = Setting::get('admin.must_change_credentials', '0') === '1'
                ? route('admin.credentials.edit')
                : route('admin.dashboard');

            return $request->header('X-Inertia')
                ? Inertia::location($destination)
                : redirect($destination, 303);
        }

        RateLimiter::hit($key, $lockoutSeconds);
        AdminLoginLog::recordLogin($request->string('username')->toString(), 'failed');

        return back()->withErrors([
            'username' => 'Invalid credentials.',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $username = session('admin_username', 'admin');
        AdminLoginLog::recordLogout($username);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function showCredentialChange(): Response
    {
        return Inertia::render('Admin/ChangeCredentials', [
            'current_username' => (string) Setting::get('admin.username', ''),
        ]);
    }

    public function changeCredentials(Request $request): RedirectResponse|SymfonyResponse
    {
        $storedUsername = (string) Setting::get('admin.username', '');
        $storedPassword = (string) Setting::get('admin.password', '');

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'username' => ['required', 'string', 'min:3', 'max:50', Rule::notIn([$storedUsername])],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ], [
            'username.not_in' => 'Choose a new administrator username.',
        ]);

        if ($storedPassword === '' || ! Hash::check($validated['current_password'], $storedPassword)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $newHash = Hash::make($validated['password']);
        $version = (string) Str::uuid();

        Setting::set('admin.username', $validated['username']);
        Setting::set('admin.password', $newHash);
        Setting::set('admin.must_change_credentials', '0');
        Setting::set('admin.credentials_version', $version);

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->regenerate();
        $request->session()->put([
            'admin_authenticated' => true,
            'admin_username' => $validated['username'],
            'admin_credentials_version' => $version,
            'admin_last_activity_at' => now()->timestamp,
        ]);

        AdminLoginLog::recordLogin($validated['username'], 'success');

        return $request->header('X-Inertia')
            ? Inertia::location(route('admin.dashboard'))
            : redirect()->route('admin.dashboard', status: 303);
    }

    private function throttleKey(Request $request): string
    {
        return 'admin-login|'.Str::lower($request->string('username')->toString()).'|'.$request->ip();
    }

    private function credentialsVersion(string $passwordHash): string
    {
        return (string) Setting::get('admin.credentials_version', hash('sha256', $passwordHash));
    }
}
