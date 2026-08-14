@php
    $code        = 403;
    $title       = 'Access denied';
    $description = isset($exception) ? $exception->getMessage() : 'You don\'t have permission to access this resource.';
    $description = $description ?: 'You don\'t have permission to access this resource.';
    $hint        = 'If you believe this is a mistake, please contact our support team.';
    $statusLabel = 'Forbidden';

    $pillBg     = 'rgba(245,158,11,0.08)';
    $pillText   = '#fbbf24';
    $pillBorder = 'rgba(245,158,11,0.2)';

    $iconBg     = 'rgba(245,158,11,0.08)';
    $iconBorder = 'rgba(245,158,11,0.15)';

    $icon = <<<'SVG'
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
    SVG;

    $actions = [
        ['type' => 'primary', 'label' => 'Go Home',          'href' => '/',                    'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'],
        ['type' => 'link',    'label' => 'Contact Support',   'href' => 'mailto:support@nexahub.io', 'icon' => ''],
    ];
@endphp
@include('errors._layout')
