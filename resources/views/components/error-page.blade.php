@props(['code', 'title', 'message', 'retryAfter' => null])
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#00D09E">
    @if (is_numeric($retryAfter))<meta http-equiv="refresh" content="{{ $retryAfter }}">@endif
    <title>{{ $code }} · finWise</title>
    <style>
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 24px; background: radial-gradient(circle at 15% 12%, #b7f5de 0, transparent 34%), linear-gradient(145deg, #effff6, #e8f2ff); color: #052224; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .error { width: min(100%, 440px); text-align: center; }
        .brand { display: inline-flex; align-items: center; gap: 9px; margin-bottom: 24px; color: #052224; font-size: 18px; font-weight: 750; letter-spacing: -.04em; text-decoration: none; }
        .brand-mark { display: grid; width: 34px; height: 34px; place-items: center; border-radius: 12px; background: #00d09e; color: #f1fff3; }
        .card { padding: 34px 28px; border: 1px solid rgb(5 34 36 / 8%); border-radius: 28px; background: rgb(255 255 255 / 90%); box-shadow: 0 24px 70px rgb(5 34 36 / 12%); }
        .code { margin: 0; color: #0068ff; font-size: clamp(64px, 18vw, 92px); font-weight: 800; letter-spacing: -.08em; line-height: .95; }
        h1 { margin: 22px 0 0; font-size: 22px; letter-spacing: -.04em; }
        p { margin: 10px 0 0; color: #456165; font-size: 14px; line-height: 1.7; }
        .home { display: inline-flex; min-height: 44px; align-items: center; justify-content: center; margin-top: 26px; padding: 0 20px; border-radius: 999px; background: #052224; color: #f1fff3; font-size: 14px; font-weight: 650; text-decoration: none; transition: transform .16s ease, background .16s ease; }
        .home:hover { transform: translateY(-1px); background: #0e3e3e; }
        .foot { margin-top: 18px; color: #527071; font-size: 12px; }
        @media (max-width: 420px) { .card { padding: 30px 20px; } }
        @media (prefers-reduced-motion: reduce) { .home { transition: none; } }
    </style>
</head>
<body>
    <main class="error">
        <a class="brand" href="{{ route('home') }}" aria-label="finWise">
            <span class="brand-mark" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 3v18m0-18c-4 0-7 2-7 5s3 4 7 4 7 1 7 4-3 5-7 5m0-18c4 0 7 2 7 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
            finWise
        </a>
        <section class="card" aria-labelledby="error-title">
            <p class="code" aria-hidden="true">{{ $code }}</p>
            <h1 id="error-title">{{ $title }}</h1>
            <p>{{ $message }}</p>
            <a class="home" href="{{ route('home') }}">{{ __('ui.error_back_home') }}</a>
        </section>
        <p class="foot">finWise · {{ __('ui.app_tagline') }}</p>
    </main>
</body>
</html>
