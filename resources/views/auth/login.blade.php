<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('icons/finwise.svg') }}" type="image/svg+xml">
    <title>{{ __('ui.login_title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-mint font-poppins text-ink">
    <main class="mx-auto flex min-h-dvh max-w-md flex-col justify-center px-6 py-12">
        <p class="text-sm font-semibold uppercase tracking-[.2em] text-ocean">finWise</p>
        <h1 class="mt-3 text-3xl font-bold">{{ __('ui.login_headline') }}</h1>
        <p class="mt-2 text-sm text-nav/70">{{ __('ui.login_intro') }}</p>
        <form method="post" action="{{ route('login.store') }}" class="mt-8 space-y-4 rounded-3xl bg-white p-6 shadow-sm">
            @csrf
            <label class="block text-sm font-medium">{{ __('ui.email') }}<input class="field mt-2" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"></label>
            <label class="block text-sm font-medium">{{ __('ui.current_password') }}<input class="field mt-2" type="password" name="password" required autocomplete="current-password"></label>
            @error('email')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            <label class="flex items-center gap-2 text-sm"><input id="remember-me" type="checkbox" name="remember" value="1" autocomplete="off"> {{ __('ui.remember_me') }}</label>
            <button class="btn w-full">{{ __('ui.login') }}</button>
        </form>
        <p class="mt-6 text-center text-sm">{{ __('ui.no_account') }} <a class="font-semibold text-ocean" href="{{ route('register') }}">{{ __('ui.register') }}</a></p>
    </main>
</body>
</html>
