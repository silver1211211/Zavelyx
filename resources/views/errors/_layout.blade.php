<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $code }} {{ $title }} — {{ config('app.name', 'NexaHub') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #060d1a;
            --card:     #0a1628;
            --border:   rgba(14,165,233,0.12);
            --sky:      #0ea5e9;
            --cyan:     #22d3ee;
            --text:     #f8fafc;
            --muted:    #94a3b8;
            --dim:      #475569;
        }

        html, body {
            min-height: 100vh;
            background: var(--bg);
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        /* Grid dot background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                radial-gradient(circle at 25% 25%, rgba(14,165,233,0.06) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(99,102,241,0.06) 0%, transparent 50%),
                radial-gradient(rgba(148,163,184,0.06) 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 32px 32px;
            pointer-events: none;
            z-index: 0;
        }

        .wrap {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Top bar ──────────────────────────────────────── */
        nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.25rem 2rem;
            border-bottom: 1px solid var(--border);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: .6rem;
            text-decoration: none;
        }

        .brand-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--sky), var(--cyan));
            box-shadow: 0 0 8px rgba(14,165,233,.6);
        }

        .brand-name {
            font-size: .9rem;
            font-weight: 800;
            letter-spacing: -.02em;
            color: var(--text);
        }

        .brand-name span {
            background: linear-gradient(90deg, var(--sky), var(--cyan));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-links { display: flex; align-items: center; gap: .75rem; }

        .nav-link {
            padding: .4rem .9rem;
            border-radius: .6rem;
            font-size: .8rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--muted);
            border: 1px solid transparent;
            transition: all .15s;
        }
        .nav-link:hover {
            color: var(--text);
            background: rgba(255,255,255,.05);
            border-color: var(--border);
        }
        .nav-link.primary {
            color: var(--sky);
            border-color: rgba(14,165,233,.25);
            background: rgba(14,165,233,.06);
        }
        .nav-link.primary:hover {
            background: rgba(14,165,233,.12);
        }

        /* ── Main content ────────────────────────────────── */
        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
        }

        .card {
            width: 100%;
            max-width: 520px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 1.5rem;
            padding: 3rem 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        /* Glow behind the code */
        .card::before {
            content: '';
            position: absolute;
            top: -60px;
            left: 50%;
            transform: translateX(-50%);
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(14,165,233,.12) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Animated pulse glow */
        @keyframes pulse-glow {
            0%, 100% { opacity: .6; transform: translateX(-50%) scale(1);   }
            50%       { opacity: 1;  transform: translateX(-50%) scale(1.15); }
        }
        .card::before { animation: pulse-glow 4s ease-in-out infinite; }

        /* Icon container */
        .icon-wrap {
            width: 72px;
            height: 72px;
            border-radius: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            position: relative;
        }

        /* Error code */
        .code {
            font-size: 6rem;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -.04em;
            background: linear-gradient(135deg, var(--sky) 0%, var(--cyan) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: .5rem;
            position: relative;
        }

        .title {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: .75rem;
            letter-spacing: -.02em;
        }

        .description {
            font-size: .9rem;
            color: var(--muted);
            line-height: 1.65;
            margin-bottom: .5rem;
        }

        .hint {
            font-size: .8rem;
            color: var(--dim);
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        /* Actions */
        .actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .6rem 1.3rem;
            border-radius: .75rem;
            font-size: .85rem;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all .15s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--sky), #2563eb);
            color: #fff;
            box-shadow: 0 4px 16px rgba(14,165,233,.25);
        }
        .btn-primary:hover {
            box-shadow: 0 6px 20px rgba(14,165,233,.35);
            transform: translateY(-1px);
        }

        .btn-ghost {
            background: rgba(255,255,255,.05);
            color: var(--muted);
            border: 1px solid rgba(255,255,255,.08);
        }
        .btn-ghost:hover {
            background: rgba(255,255,255,.09);
            color: var(--text);
        }

        /* Status pill */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .25rem .75rem;
            border-radius: 999px;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        /* ── Footer ──────────────────────────────────────── */
        footer {
            text-align: center;
            padding: 1.25rem;
            font-size: .75rem;
            color: var(--dim);
            border-top: 1px solid var(--border);
        }
        footer a { color: var(--muted); text-decoration: none; }
        footer a:hover { color: var(--sky); }

        @media (max-width: 480px) {
            nav { padding: 1rem; }
            .card { padding: 2rem 1.5rem; }
            .code { font-size: 4.5rem; }
            .title { font-size: 1.15rem; }
        }
    </style>
</head>
<body>
<div class="wrap">

    <!-- Nav -->
    <nav>
        <a href="/" class="brand">
            <span class="brand-dot"></span>
            <span class="brand-name">Nexa<span>Hub</span></span>
        </a>
        <div class="nav-links">
            <a href="javascript:history.back()" class="nav-link">← Back</a>
            <a href="/" class="nav-link primary">Go Home</a>
        </div>
    </nav>

    <!-- Main -->
    <main>
        <div class="card">

            <!-- Status pill -->
            <div class="status-pill" style="background:{{ $pillBg }}; color:{{ $pillText }}; border:1px solid {{ $pillBorder }};">
                <span class="status-dot" style="background:{{ $pillText }};"></span>
                {{ $statusLabel }}
            </div>

            <!-- Icon -->
            <div class="icon-wrap" style="background:{{ $iconBg }}; border:1px solid {{ $iconBorder }};">
                {!! $icon !!}
            </div>

            <!-- Code -->
            <div class="code">{{ $code }}</div>

            <!-- Text -->
            <h1 class="title">{{ $title }}</h1>
            <p class="description">{{ $description }}</p>
            <p class="hint">{{ $hint }}</p>

            <!-- Actions -->
            <div class="actions">
                @foreach($actions as $action)
                    @if($action['type'] === 'primary')
                        <a href="{{ $action['href'] }}" class="btn btn-primary">
                            {!! $action['icon'] ?? '' !!}
                            {{ $action['label'] }}
                        </a>
                    @elseif($action['type'] === 'back')
                        <a href="javascript:history.back()" class="btn btn-ghost">
                            {!! $action['icon'] ?? '' !!}
                            {{ $action['label'] }}
                        </a>
                    @elseif($action['type'] === 'refresh')
                        <a href="javascript:location.reload()" class="btn btn-ghost">
                            {!! $action['icon'] ?? '' !!}
                            {{ $action['label'] }}
                        </a>
                    @else
                        <a href="{{ $action['href'] }}" class="btn btn-ghost">
                            {!! $action['icon'] ?? '' !!}
                            {{ $action['label'] }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        {{ config('app.name', 'NexaHub') }} &nbsp;·&nbsp; Need help? <a href="mailto:support@nexahub.io">support@nexahub.io</a>
    </footer>

</div>
</body>
</html>
