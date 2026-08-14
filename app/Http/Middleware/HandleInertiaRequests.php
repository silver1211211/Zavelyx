<?php

namespace App\Http\Middleware;

use App\Models\Currency;
use App\Models\Setting;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    private static function brandUrl(string $key): string
    {
        $val = Setting::get($key, '');
        if (!$val) return '';
        if (str_starts_with($val, 'http')) {
            return parse_url($val, PHP_URL_PATH) ?? '';
        }
        return '/storage/' . ltrim($val, '/');
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        // Touch last_active_at every Inertia page load (cheap update, no timestamps)
        if ($user) {
            $user->touchLastActive();
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user'          => $user?->loadMissing('wallet'),
                'roles'         => $user?->getRoleNames() ?? [],
                'unread_count'  => $user ? $user->unreadNotificationsCount() : 0,
            ],
            'currencies' => Currency::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get(['code', 'name', 'symbol', 'exchange_rate', 'is_default']),
            'preferred_currency' => $user?->preferred_currency,
            'contact_link'  => Setting::get('contact.link', 'mailto:support@zavelyx.com'),
            'admin_open_tickets' => session('admin_authenticated') ? Ticket::where('admin_unread', true)->count() : 0,
            'site_settings' => [
                'name'                 => Setting::get('site.name', 'Zavelyx'),
                'tagline'              => Setting::get('site.tagline', 'Global SMS & Virtual Number Infrastructure'),
                'maintenance_mode'     => Setting::get('site.maintenance_mode', '0') === '1',
                'maintenance_message'  => Setting::get('site.maintenance_message', 'We are performing scheduled maintenance.'),
                'announcement_enabled'  => Setting::get('homepage.announcement_enabled', '0') === '1',
                'announcement_text'    => Setting::get('homepage.announcement_text', ''),
                'announcement_link'    => Setting::get('homepage.announcement_link', ''),
                'announcement_cta'     => Setting::get('homepage.announcement_cta', ''),
                'announcement_color'   => Setting::get('homepage.announcement_color', 'sky'),
                'announcement_icon'    => Setting::get('homepage.announcement_icon', ''),
                'announcement_pinned'  => Setting::get('homepage.announcement_pinned', '0') === '1',
                'hero_title'           => Setting::get('homepage.hero_title', ''),
                'hero_subtitle'        => Setting::get('homepage.hero_subtitle', ''),
                'footer_text'          => Setting::get('homepage.footer_text', '© 2026 Zavelyx. All rights reserved.'),
                'stats_activations'    => (float) Setting::get('homepage.stats_activations', '2.4'),
                'stats_countries'      => (int) Setting::get('homepage.stats_countries', '150'),
                'stats_operators'      => (int) Setting::get('homepage.stats_operators', '700'),
                'stats_success_rate'   => (float) Setting::get('homepage.stats_success_rate', '99.7'),
                'stats_uptime'         => (float) Setting::get('homepage.stats_uptime', '99.9'),
                'cta_primary_text'     => Setting::get('homepage.cta_primary_text', 'Start Receiving SMS Now'),
                'cta_secondary_text'   => Setting::get('homepage.cta_secondary_text', 'View API Docs'),
                'support_email'        => Setting::get('site.support_email', 'support@zavelyx.com'),
                'logo_url'       => self::brandUrl('brand.logo_main') ?: self::brandUrl('site.logo_url'),
                'favicon_url'    => self::brandUrl('site.favicon')    ?: self::brandUrl('site.favicon_url'),
                'logo_admin'     => self::brandUrl('brand.logo_admin'),
                'logo_dashboard' => self::brandUrl('brand.logo_dashboard'),
                'logo_auth'      => self::brandUrl('brand.logo_auth'),
                'logo_footer'    => self::brandUrl('brand.logo_footer'),
            ],
            'theme' => [
                'preset'          => Setting::get('theme.preset', 'nexahub'),
                'primary'         => Setting::get('theme.primary', '#0ea5e9'),
                'secondary'       => Setting::get('theme.secondary', '#6366f1'),
                'accent'          => Setting::get('theme.accent', '#22d3ee'),
                'dark_bg'         => Setting::get('theme.dark_bg', '#060d1a'),
                'dark_card'       => Setting::get('theme.dark_card', '#0a1628'),
                'glow'            => Setting::get('theme.glow', '#0ea5e9'),
                'glow_intensity'  => (float) Setting::get('theme.glow_intensity', '0.3'),
                'border_radius'   => Setting::get('theme.border_radius', 'xl'),
            ],
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
        ];
    }
}
