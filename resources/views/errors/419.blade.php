@php
    $code        = 419;
    $title       = 'Session expired';
    $description = 'Your session has expired for security reasons. This usually happens after a period of inactivity.';
    $hint        = 'Go back to the previous page and try submitting again — your session will be renewed automatically.';
    $statusLabel = 'Page Expired';

    $pillBg     = 'rgba(99,102,241,0.08)';
    $pillText   = '#818cf8';
    $pillBorder = 'rgba(99,102,241,0.2)';

    $iconBg     = 'rgba(99,102,241,0.08)';
    $iconBorder = 'rgba(99,102,241,0.15)';

    $icon = <<<'SVG'
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#818cf8" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/>
        <polyline points="12 6 12 12 16 14"/>
        <path d="M4.93 4.93A10 10 0 0 1 19.07 4.93"/>
        <polyline points="2 2 4.93 4.93"/>
    </svg>
    SVG;

    $actions = [
        ['type' => 'back',    'label' => '← Go Back',       'icon' => ''],
        ['type' => 'primary', 'label' => 'Go Home',          'href' => '/', 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'],
    ];
@endphp
@include('errors._layout')
