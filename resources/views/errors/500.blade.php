@php
    $code        = 500;
    $title       = 'Server error';
    $description = 'Something went wrong on our end. Our team has been notified and is working on a fix.';
    $hint        = 'This is temporary. Please try again in a few moments — we\'ll have it sorted shortly.';
    $statusLabel = 'Internal Error';

    $pillBg     = 'rgba(239,68,68,0.08)';
    $pillText   = '#f87171';
    $pillBorder = 'rgba(239,68,68,0.2)';

    $iconBg     = 'rgba(239,68,68,0.08)';
    $iconBorder = 'rgba(239,68,68,0.15)';

    $icon = <<<'SVG'
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
        <line x1="8" y1="21" x2="16" y2="21"/>
        <line x1="12" y1="17" x2="12" y2="21"/>
        <path d="M12 8v4M12 14h.01"/>
    </svg>
    SVG;

    $actions = [
        ['type' => 'refresh', 'label' => 'Try Again', 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>'],
        ['type' => 'primary', 'label' => 'Go Home',   'href' => '/', 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'],
    ];
@endphp
@include('errors._layout')
