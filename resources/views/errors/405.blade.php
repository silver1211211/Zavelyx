@php
    $code        = 405;
    $title       = 'Method not allowed';
    $description = 'The action you attempted is not allowed on this endpoint.';
    $hint        = 'This usually means you\'ve followed an invalid link or submitted a form incorrectly.';
    $statusLabel = 'Not Allowed';

    $pillBg     = 'rgba(245,158,11,0.08)';
    $pillText   = '#fbbf24';
    $pillBorder = 'rgba(245,158,11,0.2)';

    $iconBg     = 'rgba(245,158,11,0.08)';
    $iconBorder = 'rgba(245,158,11,0.15)';

    $icon = <<<'SVG'
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/>
        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
    </svg>
    SVG;

    $actions = [
        ['type' => 'back',    'label' => '← Go Back', 'icon' => ''],
        ['type' => 'primary', 'label' => 'Go Home',   'href' => '/', 'icon' => ''],
    ];
@endphp
@include('errors._layout')
