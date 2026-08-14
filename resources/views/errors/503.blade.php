@php
    $code        = 503;
    $title       = 'Under maintenance';
    $description = isset($exception) ? $exception->getMessage() : 'We\'re performing scheduled maintenance to improve your experience.';
    $description = $description ?: 'We\'re performing scheduled maintenance to improve your experience.';
    $hint        = 'We\'ll be back online shortly. Thank you for your patience.';
    $statusLabel = 'Maintenance';

    $pillBg     = 'rgba(34,211,238,0.08)';
    $pillText   = '#22d3ee';
    $pillBorder = 'rgba(34,211,238,0.2)';

    $iconBg     = 'rgba(34,211,238,0.08)';
    $iconBorder = 'rgba(34,211,238,0.15)';

    $icon = <<<'SVG'
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#22d3ee" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
    </svg>
    SVG;

    $actions = [
        ['type' => 'refresh', 'label' => 'Check Again',        'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>'],
        ['type' => 'link',    'label' => 'Contact Support',    'href' => 'mailto:support@nexahub.io', 'icon' => ''],
    ];
@endphp
@include('errors._layout')
