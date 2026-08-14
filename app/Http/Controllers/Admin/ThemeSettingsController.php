<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ThemeSettingsController extends Controller
{
    const PRESETS = [
        'nexahub' => [
            'name'        => 'NexaHub Default',
            'description' => 'Sky blue & navy — the original NexaHub palette',
            'primary'     => '#0ea5e9',
            'secondary'   => '#6366f1',
            'accent'      => '#22d3ee',
            'dark_bg'     => '#060d1a',
            'dark_card'   => '#0a1628',
            'glow'        => '#0ea5e9',
        ],
        'cyber-blue' => [
            'name'        => 'Cyber Blue',
            'description' => 'Vivid electric blue with deep midnight',
            'primary'     => '#00d4ff',
            'secondary'   => '#7c3aed',
            'accent'      => '#06b6d4',
            'dark_bg'     => '#020817',
            'dark_card'   => '#0f172a',
            'glow'        => '#00d4ff',
        ],
        'neon-purple' => [
            'name'        => 'Neon Purple',
            'description' => 'Vibrant violet with deep charcoal',
            'primary'     => '#a855f7',
            'secondary'   => '#ec4899',
            'accent'      => '#8b5cf6',
            'dark_bg'     => '#0d0714',
            'dark_card'   => '#130d1e',
            'glow'        => '#a855f7',
        ],
        'emerald-dark' => [
            'name'        => 'Emerald Dark',
            'description' => 'Rich green with dark teal backgrounds',
            'primary'     => '#10b981',
            'secondary'   => '#0ea5e9',
            'accent'      => '#34d399',
            'dark_bg'     => '#020f0a',
            'dark_card'   => '#041a0f',
            'glow'        => '#10b981',
        ],
        'midnight-pro' => [
            'name'        => 'Midnight Pro',
            'description' => 'Monochrome slate with amber accents',
            'primary'     => '#f59e0b',
            'secondary'   => '#ef4444',
            'accent'      => '#fbbf24',
            'dark_bg'     => '#07080a',
            'dark_card'   => '#0f1117',
            'glow'        => '#f59e0b',
        ],
    ];

    public function index(): Response
    {
        return Inertia::render('Admin/ThemeSettings', [
            'presets' => self::PRESETS,
            'current' => [
                'preset'    => Setting::get('theme.preset', 'nexahub'),
                'primary'   => Setting::get('theme.primary', '#0ea5e9'),
                'secondary' => Setting::get('theme.secondary', '#6366f1'),
                'accent'    => Setting::get('theme.accent', '#22d3ee'),
                'dark_bg'   => Setting::get('theme.dark_bg', '#060d1a'),
                'dark_card' => Setting::get('theme.dark_card', '#0a1628'),
                'glow'      => Setting::get('theme.glow', '#0ea5e9'),
                'glow_intensity' => Setting::get('theme.glow_intensity', '0.3'),
                'border_radius'  => Setting::get('theme.border_radius', 'xl'),
            ],
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'preset'         => ['required', 'in:' . implode(',', array_keys(self::PRESETS)) . ',custom'],
            'primary'        => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondary'      => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent'         => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'dark_bg'        => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'dark_card'      => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'glow'           => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'glow_intensity' => ['required', 'numeric', 'min:0', 'max:1'],
            'border_radius'  => ['required', 'in:none,sm,md,lg,xl,2xl'],
        ]);

        Setting::set('theme.preset',         $validated['preset']);
        Setting::set('theme.primary',         $validated['primary']);
        Setting::set('theme.secondary',       $validated['secondary']);
        Setting::set('theme.accent',          $validated['accent']);
        Setting::set('theme.dark_bg',         $validated['dark_bg']);
        Setting::set('theme.dark_card',       $validated['dark_card']);
        Setting::set('theme.glow',            $validated['glow']);
        Setting::set('theme.glow_intensity',  (string) $validated['glow_intensity']);
        Setting::set('theme.border_radius',   $validated['border_radius']);

        return back()->with('success', 'Theme settings saved. Changes apply on next page load.');
    }
}
