{{--
    Error page shell.

    Deliberately self-contained: no Vite, no Bootstrap, no JavaScript. An error
    page has to render when the asset pipeline, the database or the whole
    application is unavailable — including during `artisan down`, when the
    build manifest may not be readable at all. The tokens below mirror the
    application theme so the page still feels like part of the product.
--}}
@props([
    'code',
    'title',
    'message',
    'tone' => 'primary',
    'glyph' => '!',
])

@php
    $tones = [
        'primary' => ['#0F766E', '#F0FDFA'],
        'warning' => ['#B45309', '#FEF3C7'],
        'danger'  => ['#B91C1C', '#FEE2E2'],
        'info'    => ['#1D4ED8', '#DBEAFE'],
    ];

    [$toneColor, $toneSoft] = $tones[$tone] ?? $tones['primary'];
    $home = \Illuminate\Support\Facades\Route::has('dashboard') ? route('dashboard') : url('/');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Http\Middleware\SetLocale::direction() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light dark">
    <title>{{ $title }} — {{ config('app.name', 'RAMS') }}</title>

    <style>
        :root {
            --bg: #F6F8FA;
            --surface: #FFFFFF;
            --border: #E2E8F0;
            --text: #1E293B;
            --strong: #0F172A;
            --muted: #64748B;
            --tone: {{ $toneColor }};
            --tone-soft: {{ $toneSoft }};
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #080F1D;
                --surface: #101A2C;
                --border: #26344E;
                --text: #DCE5F2;
                --strong: #F4F8FF;
                --muted: #93A3BC;
                --tone: #5EEAD4;
                --tone-soft: rgba(45, 212, 191, 0.14);
            }
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 2rem 1.25rem;
            background: var(--bg);
            color: var(--text);
            font-family: "Inter", -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 15px;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }

        .wrap { width: 100%; max-width: 34rem; text-align: center; }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 2.5rem;
            color: var(--strong);
            font-weight: 600;
            letter-spacing: -0.01em;
            text-decoration: none;
        }

        .brand i {
            display: grid;
            place-items: center;
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: linear-gradient(140deg, #14B8A6, #115E59);
            color: #fff;
            font-size: 16px;
            font-style: normal;
            line-height: 1;
        }

        .art {
            display: grid;
            place-items: center;
            width: 76px;
            height: 76px;
            margin: 0 auto 1.5rem;
            border-radius: 18px;
            background: var(--tone-soft);
            color: var(--tone);
            font-size: 30px;
            font-weight: 700;
            line-height: 1;
        }

        .code {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--tone);
            margin: 0 0 0.5rem;
        }

        h1 {
            font-size: clamp(1.375rem, 3vw, 1.75rem);
            font-weight: 650;
            letter-spacing: -0.025em;
            color: var(--strong);
            margin: 0 0 0.625rem;
        }

        p.lede { color: var(--muted); margin: 0 0 1.75rem; }

        .actions { display: flex; flex-wrap: wrap; gap: 0.625rem; justify-content: center; }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.625rem 1.125rem;
            border-radius: 10px;
            border: 1px solid transparent;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            font-family: inherit;
            transition: background-color .15s ease, border-color .15s ease, color .15s ease;
        }

        .btn-primary { background: #0F766E; color: #fff; }
        .btn-primary:hover { background: #115E59; }

        .btn-ghost { background: var(--surface); border-color: var(--border); color: var(--text); }
        .btn-ghost:hover { border-color: var(--muted); }

        .btn:focus-visible { outline: 3px solid rgba(15, 118, 110, .45); outline-offset: 2px; }

        footer { margin-top: 3rem; font-size: 12px; color: var(--muted); }

        @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
    </style>
</head>
<body>
    <main class="wrap">
        <a class="brand" href="{{ $home }}">
            <i aria-hidden="true">☾</i>
            {{ config('app.name', 'RAMS') }}
        </a>

        <div class="art" aria-hidden="true">{{ $glyph }}</div>

        <p class="code">{{ __('ui.error_reference') }} {{ $code }}</p>
        <h1>{{ $title }}</h1>
        <p class="lede">{{ $message }}</p>

        <div class="actions">
            {{ $slot }}
        </div>

        <footer>&copy; {{ date('Y') }} {{ config('app.name', 'RAMS') }}</footer>
    </main>
</body>
</html>
