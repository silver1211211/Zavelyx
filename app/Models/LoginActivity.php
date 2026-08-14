<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginActivity extends Model
{
    protected $fillable = [
        'user_id', 'ip_address', 'user_agent', 'device_type',
        'browser', 'os', 'country', 'city', 'action', 'session_id', 'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(int $userId, string $action, \Illuminate\Http\Request $request): void
    {
        $ua     = $request->userAgent() ?? '';
        $device = self::detectDevice($ua);
        $browser= self::detectBrowser($ua);
        $os     = self::detectOs($ua);

        if ($action === 'login') {
            static::where('user_id', $userId)->update(['is_current' => false]);
        }

        static::create([
            'user_id'    => $userId,
            'ip_address' => $request->ip(),
            'user_agent' => $ua,
            'device_type'=> $device,
            'browser'    => $browser,
            'os'         => $os,
            'action'     => $action,
            'session_id' => session()->getId(),
            'is_current' => $action === 'login',
        ]);
    }

    private static function detectDevice(string $ua): string
    {
        if (preg_match('/tablet|ipad|playbook|silk/i', $ua)) return 'tablet';
        if (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/i', $ua)) return 'mobile';
        return 'desktop';
    }

    private static function detectBrowser(string $ua): string
    {
        if (str_contains($ua, 'Edg'))    return 'Edge';
        if (str_contains($ua, 'Chrome')) return 'Chrome';
        if (str_contains($ua, 'Firefox'))return 'Firefox';
        if (str_contains($ua, 'Safari')) return 'Safari';
        if (str_contains($ua, 'Opera'))  return 'Opera';
        return 'Unknown';
    }

    private static function detectOs(string $ua): string
    {
        if (str_contains($ua, 'Windows')) return 'Windows';
        if (str_contains($ua, 'Mac'))     return 'macOS';
        if (str_contains($ua, 'Linux'))   return 'Linux';
        if (str_contains($ua, 'Android')) return 'Android';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) return 'iOS';
        return 'Unknown';
    }
}
