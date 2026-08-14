@php
    $code        = 404;
    $title       = 'Page not found';
    $description = 'The page you\'re looking for doesn\'t exist or may have been moved to a different location.';
    $hint        = 'Double-check the URL, or use the links below to get back on track.';
    $statusLabel = 'Not Found';

    $pillBg     = 'rgba(14,165,233,0.08)';
    $pillText   = '#38bdf8';
    $pillBorder = 'rgba(14,165,233,0.2)';

    $iconBg     = 'rgba(14,165,233,0.08)';
    $iconBorder = 'rgba(14,165,233,0.15)';

    $icon = <<<'SVG'
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"/>
        <path d="m21 21-4.35-4.35"/>
        <path d="M8.5 8.5l5 5M13.5 8.5l-5 5"/>
    </svg>
    SVG;

    $actions = [
        ['type' => 'primary', 'label' => 'Go to Dashboard', 'href' => '/dashboard', 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'],
        ['type' => 'back',    'label' => '← Go Back',       'icon' => ''],
    ];
@endphp
@include('errors._layout')
