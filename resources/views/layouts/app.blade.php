<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #16181d;
            --muted: #5f6877;
            --line: #d9dee8;
            --surface: #f7f8fb;
            --accent: #0f766e;
            --accent-strong: #134e4a;
            --warn: #92400e;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: var(--ink);
            background: var(--surface);
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.5;
        }

        header, main {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 24px 0 18px;
        }

        nav {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        a {
            color: var(--accent-strong);
            text-decoration-thickness: 1px;
            text-underline-offset: 3px;
        }

        .brand {
            font-size: 1.15rem;
            font-weight: 800;
            text-decoration: none;
            color: var(--ink);
        }

        .hero {
            display: grid;
            gap: 16px;
            padding: 36px 0 28px;
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
        }

        h1 {
            max-width: 850px;
            margin: 0;
            font-size: clamp(2rem, 6vw, 4.75rem);
            line-height: 1;
            letter-spacing: 0;
        }

        h2, h3, p {
            margin-top: 0;
        }

        .lede {
            max-width: 760px;
            color: var(--muted);
            font-size: 1.12rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin: 28px 0;
        }

        .card {
            min-height: 180px;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: white;
        }

        .meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin: 14px 0;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            min-height: 28px;
            padding: 4px 9px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: #fff;
            color: var(--ink);
        }

        .details {
            display: grid;
            gap: 10px;
            margin: 16px 0;
            padding: 0;
        }

        .details div {
            display: grid;
            gap: 3px;
        }

        .details dt {
            color: var(--muted);
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .details dd {
            margin: 0;
            overflow-wrap: anywhere;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 9px 14px;
            border-radius: 6px;
            background: var(--accent);
            color: #fff;
            font-weight: 700;
            text-decoration: none;
        }

        .button-secondary {
            border: 1px solid var(--line);
            background: #fff;
            color: var(--accent-strong);
        }

        .notice {
            padding: 16px;
            border: 1px solid #f2c97d;
            border-radius: 8px;
            background: #fff7e6;
            color: var(--warn);
        }

        .form {
            display: grid;
            gap: 14px;
            width: min(520px, 100%);
            margin: 28px 0;
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            align-items: end;
            margin: 18px 0 24px;
        }

        .actions {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        label {
            display: grid;
            gap: 6px;
            font-weight: 700;
        }

        input, select, textarea {
            min-height: 42px;
            padding: 9px 11px;
            border: 1px solid var(--line);
            border-radius: 6px;
            font: inherit;
        }

        textarea {
            min-height: 128px;
            resize: vertical;
        }

        button.button {
            border: 0;
            cursor: pointer;
        }

        .link-button {
            padding: 0;
            border: 0;
            background: transparent;
            color: var(--accent-strong);
            font: inherit;
            text-decoration: underline;
            text-decoration-thickness: 1px;
            text-underline-offset: 3px;
            cursor: pointer;
        }

        .error {
            margin: -6px 0 0;
            color: #b91c1c;
        }
    </style>
</head>
<body>
    <header>
        <a class="brand" href="{{ route('home') }}">Safedrop</a>
        <nav aria-label="Primary">
            <a href="{{ route('home') }}">Discovery</a>
            <a href="{{ route('home') }}#projects">Projects</a>
            <a href="{{ route('rights.create') }}">Rights</a>
            @auth
                <a href="{{ route('account.show') }}">Account</a>
                @if (auth()->user()->canPublishProjects())
                    <a href="{{ route('creator.dashboard') }}">Creator</a>
                @endif
                @if (auth()->user()->canModerateContent())
                    <a href="{{ route('moderation.index') }}">Moderation</a>
                @endif
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="link-button" type="submit">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}">Login</a>
                <a href="{{ route('register') }}">Register</a>
            @endauth
        </nav>
    </header>

    <main>
        @yield('content')
    </main>
</body>
</html>
