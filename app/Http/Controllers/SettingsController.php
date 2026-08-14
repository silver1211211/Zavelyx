<?php

namespace App\Http\Controllers;

use App\Models\LoginActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user()->load(['wallet', 'orders', 'transactions']);

        $orders        = $user->orders;
        $totalOrders   = $orders->count();
        $successOrders = $orders->where('status', 'completed')->count();
        $cancelOrders  = $orders->whereIn('status', ['cancelled', 'refunded'])->count();

        $totalDeposited = $user->transactions()
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->sum('amount');

        $totalSpent = $user->transactions()
            ->where('type', 'order_debit')
            ->sum('amount');

        $referralCount = $user->referrals()->count();

        $loginSessions = LoginActivity::where('user_id', $user->id)
            ->where('action', 'login')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'ip_address', 'device_type', 'browser', 'os', 'country', 'city', 'is_current', 'created_at']);

        $apiToken = $user->tokens()->where('name', 'api-key')->latest()->first();

        return Inertia::render('Settings', [
            'user' => [
                ...$user->only(['id', 'name', 'email', 'avatar', 'phone', 'country', 'timezone',
                    'account_level', 'referral_code', 'referral_bonus', 'created_at', 'last_active_at']),
                'email_verified' => ! is_null($user->email_verified_at),
                'roles'          => $user->getRoleNames(),
                'preferences'    => $user->preferences ?? [],
            ],
            'stats' => [
                'balance'        => (float) ($user->wallet?->balance ?? 0),
                'total_spent'    => (float) $totalSpent,
                'total_deposited'=> (float) $totalDeposited,
                'total_orders'   => $totalOrders,
                'success_orders' => $successOrders,
                'cancel_orders'  => $cancelOrders,
                'referral_count' => $referralCount,
                'referral_bonus' => (float) ($user->referral_bonus ?? 0),
            ],
            'loginSessions'  => $loginSessions,
            'hasApiKey'      => $apiToken !== null,
            'apiKeyCreatedAt'=> $apiToken?->created_at,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone'    => ['nullable', 'string', 'max:32'],
            'country'  => ['nullable', 'string', 'max:64'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ]);

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ]);

        $user = $request->user();

        // Delete old avatar
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return back()->with('success', 'Avatar updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $request->validate([
            'preferences' => ['nullable', 'array'],
        ]);

        $user = $request->user();
        $current = $user->preferences ?? [];
        $user->update(['preferences' => array_merge($current, $request->preferences ?? [])]);

        return back()->with('success', 'Preferences saved.');
    }

    public function updateCurrency(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'currency' => ['required', 'string', 'max:10'],
        ]);

        $request->user()->update(['preferred_currency' => strtoupper($request->currency)]);

        return response()->json(['ok' => true]);
    }

    public function revokeSession(Request $request, int $sessionId): RedirectResponse
    {
        LoginActivity::where('id', $sessionId)
            ->where('user_id', $request->user()->id)
            ->delete();

        return back()->with('success', 'Session revoked.');
    }

    public function revokeAllSessions(Request $request): RedirectResponse
    {
        $user = $request->user();

        LoginActivity::where('user_id', $user->id)
            ->where('is_current', false)
            ->delete();

        return back()->with('success', 'All other sessions revoked.');
    }
}
