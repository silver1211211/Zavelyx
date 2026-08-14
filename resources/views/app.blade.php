<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>
            // Apply the saved theme before CSS and Vue load to prevent a flash of
            // the opposite palette and keep every page on one shared preference.
            (() => {
                let theme = 'light';
                try {
                    const saved = localStorage.getItem('nexahub-theme');
                    if (saved === 'light' || saved === 'dark') theme = saved;
                } catch {}
                document.documentElement.classList.toggle('dark', theme === 'dark');
                document.documentElement.dataset.theme = theme;
                document.documentElement.style.colorScheme = theme;
            })();
        </script>
        @php
            $siteName    = \App\Models\Setting::get('site.name', 'Zavelyx');
            $seoDesc     = \App\Models\Setting::get('seo.meta_description', 'Zavelyx — Global SMS & Virtual Number Infrastructure. Receive OTPs instantly on 150+ countries.');
            $seoKeywords = \App\Models\Setting::get('seo.meta_keywords', 'virtual number, OTP, SMS verification, receive SMS');
            $ogTitle     = \App\Models\Setting::get('seo.og_title')     ?: \App\Models\Setting::get('seo.meta_title', $siteName);
            $ogDesc      = \App\Models\Setting::get('seo.og_description') ?: $seoDesc;
            $ogImage     = \App\Models\Setting::get('seo.og_image_url', '');
            $twTitle     = \App\Models\Setting::get('seo.twitter_title')  ?: $ogTitle;
            $twDesc      = \App\Models\Setting::get('seo.twitter_description') ?: $ogDesc;
            $themePrimary   = \App\Models\Setting::get('theme.primary',   '#0ea5e9');
            $themeSecondary = \App\Models\Setting::get('theme.secondary', '#6366f1');
            $themeAccent    = \App\Models\Setting::get('theme.accent',    '#22d3ee');
            $themeDarkBg    = \App\Models\Setting::get('theme.dark_bg',   '#060d1a');
            $themeDarkCard  = \App\Models\Setting::get('theme.dark_card', '#0a1628');
            $themeGlow      = \App\Models\Setting::get('theme.glow',      '#0ea5e9');
            $glowIntensity  = \App\Models\Setting::get('theme.glow_intensity', '0.3');

            // Branding: normalize to root-relative URL
            $faviconRaw = \App\Models\Setting::get('site.favicon', '')
                       ?: \App\Models\Setting::get('site.favicon_url', '');
            $faviconUrl = '';
            if ($faviconRaw) {
                $faviconUrl = str_starts_with($faviconRaw, 'http')
                    ? (parse_url($faviconRaw, PHP_URL_PATH) ?? '')
                    : '/storage/' . ltrim($faviconRaw, '/');
            }
        @endphp
        <meta name="description" content="{{ $seoDesc }}">
        <meta name="keywords" content="{{ $seoKeywords }}">
        <meta property="og:title" content="{{ $ogTitle }}">
        <meta property="og:description" content="{{ $ogDesc }}">
        @if($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
        <meta property="og:type" content="website">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $twTitle }}">
        <meta name="twitter:description" content="{{ $twDesc }}">
        @if($ogImage)<meta name="twitter:image" content="{{ $ogImage }}">@endif

        <title inertia>{{ config('app.name', $siteName) }}</title>

        <!-- Favicon -->
        @if($faviconUrl)
        <link rel="icon" type="image/x-icon" href="{{ $faviconUrl }}">
        <link rel="shortcut icon" href="{{ $faviconUrl }}">
        <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
        @endif

        <!-- CSS Theme Variables -->
        <style>
            :root {
                --color-primary:   {{ $themePrimary }};
                --color-secondary: {{ $themeSecondary }};
                --color-accent:    {{ $themeAccent }};
                --color-dark-bg:   {{ $themeDarkBg }};
                --color-dark-card: {{ $themeDarkCard }};
                --color-glow:      {{ $themeGlow }};
                --glow-intensity:  {{ $glowIntensity }};
            }
        </style>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-white text-slate-900 dark:bg-slate-950 dark:text-white">
        @inertia
    </body>
</html>
