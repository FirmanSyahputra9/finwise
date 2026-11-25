<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#00D09E">
    <script>
        (() => {
            if (window.finwiseAppearance) return;
            const defaults = { theme: 'light', accent: 'green', textColor: 'default', font: 'poppins', size: '16' };
            const allowed = {
                theme: ['light', 'dark'],
                accent: ['green', 'blue', 'pink', 'purple', 'orange', 'red'],
                textColor: ['default', 'black', 'white', 'blue', 'pink', 'green'],
                font: ['poppins', 'system', 'arial', 'georgia'],
                size: ['14', '16', '18', '20'],
            };
            const normalize = (value = {}) => Object.fromEntries(
                Object.keys(defaults).map((key) => [key, allowed[key].includes(value[key]) ? value[key] : defaults[key]])
            );
            const apply = (value) => {
                const settings = normalize(value);
                const root = document.documentElement;
                root.dataset.theme = settings.theme;
                root.dataset.accent = settings.accent;
                root.dataset.textColor = settings.textColor;
                root.dataset.font = settings.font;
                root.dataset.fontSize = settings.size;
                return settings;
            };
            window.finwiseAppearance = {
                read() {
                    try { return normalize(JSON.parse(localStorage.getItem('finwise-appearance') || '{}')); }
                    catch (_) { return { ...defaults }; }
                },
                save(value) {
                    const settings = apply(value);
                    try { localStorage.setItem('finwise-appearance', JSON.stringify(settings)); } catch (_) {}
                    return settings;
                },
                apply,
            };
            apply(window.finwiseAppearance.read());
            document.addEventListener('livewire:navigated', () => {
                window.finwiseAppearance.apply(window.finwiseAppearance.read());
            });
            window.addEventListener('storage', (event) => {
                if (event.key === 'finwise-appearance') {
                    try { apply(JSON.parse(event.newValue || '{}')); } catch (_) { apply(defaults); }
                }
            });
        })();
    </script>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset('icons/finwise.svg') }}" type="image/svg+xml">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <title>{{ $title ?? 'finWise' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600&family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;1,500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-200 font-poppins">
    <div class="relative mx-auto min-h-dvh max-w-[430px] bg-main pb-24 shadow-xl">
        {{ $slot }}
        <div class="absolute right-5 top-12 z-40"><livewire:notifications-menu :key="'global-notifications'" /></div>
        <div id="offline-sync-status" class="fixed bottom-[96px] left-1/2 z-40 hidden w-[min(390px,calc(100vw-32px))] -translate-x-1/2 rounded-xl bg-white p-3 text-xs text-dark shadow-lg" role="status"></div>
        <button id="pwa-install-button" type="button" class="fixed right-4 top-3 z-40 hidden rounded-full bg-dark px-4 py-2 text-xs font-medium text-mint shadow">{{ __('ui.install_app') }}</button>

        <nav wire:transition.navigate="bottom-navigation" class="fixed inset-x-0 bottom-0 z-30 mx-auto flex h-[88px] max-w-[430px] items-center justify-between rounded-t-[50px] bg-light px-9">
            @foreach ([['dashboard', 'home', __('ui.home')], ['analysis', 'analysis', __('ui.analysis')], ['transactions', 'transactions', __('ui.transactions')], ['savings-goals', 'savings', __('ui.savings')], ['categories', 'category', __('ui.categories')], ['profile', 'profile', __('ui.profile')]] as [$route, $icon, $label])
                <a href="{{ route($route) }}" wire:navigate
                   aria-label="{{ $label }}" class="flex h-[58px] min-w-10 flex-col items-center justify-center gap-0.5 rounded-[18px] px-1 text-nav {{ request()->routeIs($route) ? 'bg-main' : '' }}">
                    <x-icon :name="$icon" class="size-5" />
                    <span class="text-[9px] font-medium leading-none">{{ $label }}</span>
                </a>
            @endforeach
        </nav>
    </div>
    @livewireScripts
    <script src="{{ asset('offline.js') }}" defer data-navigate-once></script>
    @stack('scripts')
</body>
</html>
