<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLoginLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthController extends Controller
{
    private const DEFAULT_USERNAME = 'admin';
    private const DEFAULT_PASSWORD = '1234';

    public function showLogin(): Response|RedirectResponse
    {
        if (session('admin_authenticated')) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Admin/Login');
    }

    public function login(Request $request): RedirectResponse|SymfonyResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $storedUsername = Setting::get('admin.username', self::DEFAULT_USERNAME);
        $storedPassword = Setting::get('admin.password', '');

        $usernameMatch = $request->username === $storedUsername;

        // Check hashed password from Setting, fall back to plain dev default
        if ($storedPassword) {
            $passwordMatch = password_verify($request->password, $storedPassword);
        } else {
            $passwordMatch = $request->password === self::DEFAULT_PASSWORD;
        }

        if ($usernameMatch && $passwordMatch) {
            $request->session()->regenerate();
            $request->session()->put([
                'admin_authenticated' => true,
                'admin_username' => $storedUsername,
            ]);
            $request->session()->save();

            AdminLoginLog::recordLogin($storedUsername, 'success');

            if ($request->header('X-Inertia')) {
                return Inertia::location(route('admin.dashboard'));
            }

            return redirect()->route('admin.dashboard', status: 303);
        }

        AdminLoginLog::recordLogin($request->username, 'failed');

        return back()->withErrors([
            'username' => 'Invalid credentials.',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $username = session('admin_username', 'admin');
        AdminLoginLog::recordLogout($username);

        $request->session()->forget(['admin_authenticated', 'admin_username']);

        return redirect()->route('admin.login');
    }
}
