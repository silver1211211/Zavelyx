<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminLoginLog extends Model
{
    protected $fillable = [
        'admin_username',
        'action',
        'ip_address',
        'user_agent',
        'status',
        'session_id',
        'login_at',
        'logout_at',
        'duration_minutes',
    ];

    protected $casts = [
        'login_at'  => 'datetime',
        'logout_at' => 'datetime',
    ];

    public static function recordLogin(string $username, string $status = 'success'): self
    {
        return static::create([
            'admin_username' => $username,
            'action'         => 'login',
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'status'         => $status,
            'session_id'     => session()->getId(),
            'login_at'       => now(),
        ]);
    }

    public static function recordLogout(string $username): void
    {
        $sessionId = session()->getId();

        $log = static::where('admin_username', $username)
            ->where('session_id', $sessionId)
            ->where('action', 'login')
            ->whereNull('logout_at')
            ->latest()
            ->first();

        if ($log) {
            $duration = $log->login_at ? (int) $log->login_at->diffInMinutes(now()) : null;
            $log->update([
                'logout_at'        => now(),
                'duration_minutes' => $duration,
            ]);
        }
    }

    public function browserLabel(): string
    {
        $ua = $this->user_agent ?? '';
        if (str_contains($ua, 'Edg'))     return 'Edge';
        if (str_contains($ua, 'Chrome'))  return 'Chrome';
        if (str_contains($ua, 'Firefox')) return 'Firefox';
        if (str_contains($ua, 'Safari'))  return 'Safari';
        return 'Unknown';
    }

    public function osLabel(): string
    {
        $ua = $this->user_agent ?? '';
        if (str_contains($ua, 'Windows')) return 'Windows';
        if (str_contains($ua, 'Mac'))     return 'macOS';
        if (str_contains($ua, 'Linux'))   return 'Linux';
        if (str_contains($ua, 'Android')) return 'Android';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) return 'iOS';
        return 'Unknown';
    }
}
