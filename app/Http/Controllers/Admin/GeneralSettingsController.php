<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GeneralSettingsController extends Controller
{
    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Normalise a stored branding value to a root-relative URL.
     * Old format: "http://127.0.0.1:8000/storage/logos/file.png"
     * New format: "logos/file.png"
     * Returns:    "/storage/logos/file.png"  (always root-relative)
     */
    private static function brandUrl(string $key): string
    {
        $val = Setting::get($key, '');
        if (!$val) return '';
        // Legacy: full URL stored → extract path only
        if (str_starts_with($val, 'http')) {
            return parse_url($val, PHP_URL_PATH) ?? '';
        }
        // New: relative path stored
        return '/storage/' . ltrim($val, '/');
    }

    /** Allowed logo slot types */
    private const LOGO_TYPES = ['main', 'admin', 'dashboard', 'auth', 'footer', 'favicon'];

    /** Setting key for each type */
    private static function logoKey(string $type): string
    {
        return $type === 'favicon' ? 'site.favicon' : "brand.logo_{$type}";
    }

    // ── loadAll() ─────────────────────────────────────────────────────────────

    private function loadAll(): array
    {
        return [
            'general' => [
                'site_name'            => Setting::get('site.name', 'NexaHub'),
                'site_tagline'         => Setting::get('site.tagline', 'Global SMS & Virtual Number Infrastructure'),
                'site_description'     => Setting::get('site.description', 'NexaHub is a premium virtual number and OTP activation platform.'),
                'support_email'        => Setting::get('site.support_email', 'support@nexahub.io'),
                'support_phone'        => Setting::get('site.support_phone', ''),
                'support_telegram'     => Setting::get('site.support_telegram', ''),
                'support_whatsapp'     => Setting::get('site.support_whatsapp', ''),
                'support_discord'      => Setting::get('site.support_discord', ''),
                'contact_link'         => Setting::get('contact.link', 'mailto:support@nexahub.io'),
                'business_address'     => Setting::get('site.business_address', ''),
                'timezone'             => Setting::get('site.timezone', 'UTC'),
                'maintenance_mode'     => Setting::get('site.maintenance_mode', '0') === '1',
                'maintenance_message'  => Setting::get('site.maintenance_message', 'We are performing scheduled maintenance. We\'ll be back shortly.'),
            ],
            'seo' => [
                'meta_title'          => Setting::get('seo.meta_title', 'NexaHub — Global SMS & Virtual Number Infrastructure'),
                'meta_description'    => Setting::get('seo.meta_description', 'Receive OTPs instantly on 150+ countries and 700+ operators.'),
                'meta_keywords'       => Setting::get('seo.meta_keywords', 'virtual number, OTP, SMS verification, receive SMS, phone number'),
                'og_title'            => Setting::get('seo.og_title', ''),
                'og_description'      => Setting::get('seo.og_description', ''),
                'og_image_url'        => Setting::get('seo.og_image_url', ''),
                'twitter_title'       => Setting::get('seo.twitter_title', ''),
                'twitter_description' => Setting::get('seo.twitter_description', ''),
            ],
            'homepage' => [
                'hero_title'            => Setting::get('homepage.hero_title', ''),
                'hero_subtitle'         => Setting::get('homepage.hero_subtitle', ''),
                'announcement_enabled'  => Setting::get('homepage.announcement_enabled', '0') === '1',
                'announcement_text'     => Setting::get('homepage.announcement_text', ''),
                'announcement_link'     => Setting::get('homepage.announcement_link', ''),
                'announcement_cta'      => Setting::get('homepage.announcement_cta', ''),
                'announcement_color'    => Setting::get('homepage.announcement_color', 'sky'),
                'announcement_icon'     => Setting::get('homepage.announcement_icon', ''),
                'announcement_pinned'   => Setting::get('homepage.announcement_pinned', '0') === '1',
                'footer_text'           => Setting::get('homepage.footer_text', '© 2026 NexaHub. All rights reserved.'),
                'stats_activations'     => Setting::get('homepage.stats_activations', '2.4'),
                'stats_countries'       => Setting::get('homepage.stats_countries', '150'),
                'stats_operators'       => Setting::get('homepage.stats_operators', '700'),
                'stats_success_rate'    => Setting::get('homepage.stats_success_rate', '99.7'),
                'stats_uptime'          => Setting::get('homepage.stats_uptime', '99.9'),
                'cta_primary_text'      => Setting::get('homepage.cta_primary_text', 'Start Receiving SMS Now'),
                'cta_secondary_text'    => Setting::get('homepage.cta_secondary_text', 'View API Docs'),
            ],
            'branding' => [
                'logo_main'      => self::brandUrl('brand.logo_main')      ?: self::brandUrl('site.logo_url'),
                'logo_admin'     => self::brandUrl('brand.logo_admin'),
                'logo_dashboard' => self::brandUrl('brand.logo_dashboard'),
                'logo_auth'      => self::brandUrl('brand.logo_auth'),
                'logo_footer'    => self::brandUrl('brand.logo_footer'),
                'favicon'        => self::brandUrl('site.favicon')         ?: self::brandUrl('site.favicon_url'),
            ],
        ];
    }

    // ── Controller actions ────────────────────────────────────────────────────

    public function index(): Response
    {
        return Inertia::render('Admin/GeneralSettings', $this->loadAll());
    }

    public function saveGeneral(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name'           => ['required', 'string', 'max:100'],
            'site_tagline'        => ['nullable', 'string', 'max:200'],
            'site_description'    => ['nullable', 'string', 'max:500'],
            'support_email'       => ['nullable', 'email', 'max:200'],
            'support_phone'       => ['nullable', 'string', 'max:50'],
            'support_telegram'    => ['nullable', 'string', 'max:200'],
            'support_whatsapp'    => ['nullable', 'string', 'max:200'],
            'support_discord'     => ['nullable', 'string', 'max:200'],
            'contact_link'        => ['nullable', 'string', 'max:500'],
            'business_address'    => ['nullable', 'string', 'max:500'],
            'timezone'            => ['required', 'string', 'max:50'],
            'maintenance_mode'    => ['boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:1000'],
        ]);

        Setting::set('site.name',             $validated['site_name']);
        Setting::set('site.tagline',          $validated['site_tagline']       ?? '');
        Setting::set('site.description',      $validated['site_description']   ?? '');
        Setting::set('site.support_email',    $validated['support_email']      ?? '');
        Setting::set('site.support_phone',    $validated['support_phone']      ?? '');
        Setting::set('site.support_telegram', $validated['support_telegram']   ?? '');
        Setting::set('site.support_whatsapp', $validated['support_whatsapp']   ?? '');
        Setting::set('site.support_discord',  $validated['support_discord']    ?? '');
        Setting::set('contact.link',          $validated['contact_link']       ?? 'mailto:support@nexahub.io');
        Setting::set('site.business_address', $validated['business_address']   ?? '');
        Setting::set('site.timezone',         $validated['timezone']);
        Setting::set('site.maintenance_mode', ($validated['maintenance_mode']  ?? false) ? '1' : '0');
        Setting::set('site.maintenance_message', $validated['maintenance_message'] ?? '');

        return back()->with('success', 'General settings saved.');
    }

    public function saveSeo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meta_title'          => ['nullable', 'string', 'max:200'],
            'meta_description'    => ['nullable', 'string', 'max:500'],
            'meta_keywords'       => ['nullable', 'string', 'max:500'],
            'og_title'            => ['nullable', 'string', 'max:200'],
            'og_description'      => ['nullable', 'string', 'max:500'],
            'og_image_url'        => ['nullable', 'url', 'max:500'],
            'twitter_title'       => ['nullable', 'string', 'max:200'],
            'twitter_description' => ['nullable', 'string', 'max:500'],
        ]);

        Setting::set('seo.meta_title',          $validated['meta_title']          ?? '');
        Setting::set('seo.meta_description',    $validated['meta_description']    ?? '');
        Setting::set('seo.meta_keywords',       $validated['meta_keywords']       ?? '');
        Setting::set('seo.og_title',            $validated['og_title']            ?? '');
        Setting::set('seo.og_description',      $validated['og_description']      ?? '');
        Setting::set('seo.og_image_url',        $validated['og_image_url']        ?? '');
        Setting::set('seo.twitter_title',       $validated['twitter_title']       ?? '');
        Setting::set('seo.twitter_description', $validated['twitter_description'] ?? '');

        return back()->with('success', 'SEO settings saved.');
    }

    public function saveHomepage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hero_title'           => ['nullable', 'string', 'max:200'],
            'hero_subtitle'        => ['nullable', 'string', 'max:500'],
            'announcement_enabled' => ['boolean'],
            'announcement_text'    => ['nullable', 'string', 'max:300'],
            'announcement_link'    => ['nullable', 'url', 'max:500'],
            'announcement_cta'     => ['nullable', 'string', 'max:60'],
            'announcement_color'   => ['nullable', 'string', 'in:sky,violet,emerald,amber,rose,gradient'],
            'announcement_icon'    => ['nullable', 'string', 'max:10'],
            'announcement_pinned'  => ['boolean'],
            'footer_text'          => ['nullable', 'string', 'max:300'],
            'stats_activations'    => ['nullable', 'numeric', 'min:0'],
            'stats_countries'      => ['nullable', 'integer', 'min:0'],
            'stats_operators'      => ['nullable', 'integer', 'min:0'],
            'stats_success_rate'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'stats_uptime'         => ['nullable', 'numeric', 'min:0', 'max:100'],
            'cta_primary_text'     => ['nullable', 'string', 'max:100'],
            'cta_secondary_text'   => ['nullable', 'string', 'max:100'],
        ]);

        Setting::set('homepage.hero_title',           $validated['hero_title']          ?? '');
        Setting::set('homepage.hero_subtitle',        $validated['hero_subtitle']        ?? '');
        Setting::set('homepage.announcement_enabled', ($validated['announcement_enabled'] ?? false) ? '1' : '0');
        Setting::set('homepage.announcement_text',   $validated['announcement_text']   ?? '');
        Setting::set('homepage.announcement_link',   $validated['announcement_link']   ?? '');
        Setting::set('homepage.announcement_cta',    $validated['announcement_cta']    ?? '');
        Setting::set('homepage.announcement_color',  $validated['announcement_color']  ?? 'sky');
        Setting::set('homepage.announcement_icon',   $validated['announcement_icon']   ?? '');
        Setting::set('homepage.announcement_pinned', ($validated['announcement_pinned'] ?? false) ? '1' : '0');
        Setting::set('homepage.footer_text',          $validated['footer_text']          ?? '© 2026 NexaHub. All rights reserved.');
        Setting::set('homepage.stats_activations',    (string) ($validated['stats_activations'] ?? '2.4'));
        Setting::set('homepage.stats_countries',      (string) ($validated['stats_countries']    ?? '150'));
        Setting::set('homepage.stats_operators',      (string) ($validated['stats_operators']    ?? '700'));
        Setting::set('homepage.stats_success_rate',   (string) ($validated['stats_success_rate'] ?? '99.7'));
        Setting::set('homepage.stats_uptime',         (string) ($validated['stats_uptime']        ?? '99.9'));
        Setting::set('homepage.cta_primary_text',     $validated['cta_primary_text']     ?? 'Start Receiving SMS Now');
        Setting::set('homepage.cta_secondary_text',   $validated['cta_secondary_text']   ?? 'View API Docs');

        return back()->with('success', 'Homepage settings saved.');
    }

    // ── Branding uploads ──────────────────────────────────────────────────────

    /**
     * Upload a branding image.  type = main|admin|dashboard|auth|footer|favicon
     */
    public function uploadBranding(Request $request, string $type): RedirectResponse
    {
        if (!in_array($type, self::LOGO_TYPES)) {
            abort(404);
        }

        $isFavicon = $type === 'favicon';

        $field = $isFavicon ? 'favicon' : 'logo';
        $rules = $isFavicon
            ? ['required', 'file', 'max:512', 'mimes:ico,png,jpg,jpeg,webp,svg']
            : ['required', 'image', 'max:2048', 'mimes:png,jpg,jpeg,webp,svg'];

        $request->validate([$field => $rules]);

        Storage::disk('public')->makeDirectory('logos');

        $key    = self::logoKey($type);
        $oldVal = Setting::get($key, '');

        // Delete the old file if it's a stored path
        if ($oldVal && !str_starts_with($oldVal, 'http')) {
            Storage::disk('public')->delete($oldVal);
        } elseif ($oldVal && str_starts_with($oldVal, 'http')) {
            // Legacy full URL: extract relative path
            $oldPath = ltrim(str_replace(
                rtrim(env('APP_URL', 'http://localhost'), '/') . '/storage',
                '',
                $oldVal
            ), '/');
            Storage::disk('public')->delete($oldPath);
        }

        $path = $request->file($field)->store('logos', 'public');
        Setting::set($key, $path); // store relative path only: "logos/filename.ext"

        $label = ucfirst($type);
        return back()->with('success', "{$label} image uploaded successfully.");
    }

    /**
     * Delete a branding image.
     */
    public function deleteBranding(Request $request, string $type): RedirectResponse
    {
        if (!in_array($type, self::LOGO_TYPES)) {
            abort(404);
        }

        $key = self::logoKey($type);
        $val = Setting::get($key, '');

        if ($val) {
            if (!str_starts_with($val, 'http')) {
                Storage::disk('public')->delete($val);
            }
            Setting::set($key, '');
        }

        // Also clear legacy keys if type is main
        if ($type === 'main') {
            Setting::set('site.logo_url', '');
        }
        if ($type === 'favicon') {
            Setting::set('site.favicon_url', '');
        }

        return back()->with('success', ucfirst($type) . ' image removed.');
    }

    // ── Legacy endpoints kept for backward compatibility ────────────────────

    public function uploadLogo(Request $request): RedirectResponse
    {
        return $this->uploadBranding($request, 'main');
    }

    public function uploadFavicon(Request $request): RedirectResponse
    {
        return $this->uploadBranding($request, 'favicon');
    }
}
