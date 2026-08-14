@php
    $code        = 429;
    $title       = 'Too many requests';
    $description = 'You\'ve sent too many requests in a short period of time. Our systems have temporarily limited your access.';
    $hint        = 'Please wait a moment before trying again. Your access will be restored automatically.';
    $statusLabel = 'Rate Limited';

    $pillBg     = 'rgba(249,115,22,0.08)';
    $pillText   = '#fb923c';
    $pillBorder = 'rgba(249,115,22,0.2)';

    $iconBg     = 'rgba(249,115,22,0.08)';
    $iconBorder = 'rgba(249,115,22,0.15)';

    $icon = <<<'SVG'
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fb923c" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>
    </svg>
    SVG;

    $actions = [
        ['type' => 'refresh', 'label' => 'Try Again',  'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>'],
        ['type' => 'primary', 'label' => 'Go Home',    'href' => '/', 'icon' => ''],
    ];
@endphp
@include('errors._layout')
