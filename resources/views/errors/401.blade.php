@php
    $code        = 401;
    $title       = 'Authentication required';
    $description = 'You need to be signed in to access this page.';
    $hint        = 'Please log in with your credentials to continue.';
    $statusLabel = 'Unauthorized';

    $pillBg     = 'rgba(14,165,233,0.08)';
    $pillText   = '#38bdf8';
    $pillBorder = 'rgba(14,165,233,0.2)';

    $iconBg     = 'rgba(14,165,233,0.08)';
    $iconBorder = 'rgba(14,165,233,0.15)';

    $icon = <<<'SVG'
    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
    </svg>
    SVG;

    $actions = [
        ['type' => 'primary', 'label' => 'Sign In',  'href' => '/login', 'icon' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>'],
        ['type' => 'link',    'label' => 'Go Home',  'href' => '/', 'icon' => ''],
    ];
@endphp
@include('errors._layout')
