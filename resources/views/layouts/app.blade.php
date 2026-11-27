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
            const defaults = { theme: 'light', accent: 'green', textColor: 'default', font: 'poppins', size: '16', pattern: 'none' };
            const allowed = {
                theme: ['light', 'dark'],
                accent: ['green', 'blue', 'pink', 'purple', 'orange', 'red'],
                textColor: ['default', 'black', 'white', 'blue', 'pink', 'green'],
                font: ['poppins', 'system', 'arial', 'georgia'],
                size: ['14', '16', '18', '20'],
                pattern: ['none', 'fuzzy-duck-40', 'rare-snake-68'],
            };
            const normalize = (value = {}) => Object.fromEntries(
                Object.keys(defaults).map((key) => [key, allowed[key].includes(value[key]) ? value[key] : defaults[key]])
            );
            const root = document.documentElement;
            const apply = (value) => {
                const settings = normalize(value);
                root.dataset.theme = settings.theme;
                root.dataset.accent = settings.accent;
                root.dataset.textColor = settings.textColor;
                root.dataset.font = settings.font;
                root.dataset.fontSize = settings.size;
                root.dataset.pattern = settings.pattern;
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
            const appearanceAttributes = ['data-theme', 'data-accent', 'data-text-color', 'data-font', 'data-font-size', 'data-pattern'];
            new MutationObserver(() => {
                const settings = window.finwiseAppearance.read();
                const values = [settings.theme, settings.accent, settings.textColor, settings.font, settings.size, settings.pattern];
                if (appearanceAttributes.some((attribute, index) => root.getAttribute(attribute) !== values[index])) apply(settings);
            }).observe(root, { attributes: true, attributeFilter: appearanceAttributes });
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
    <link rel="stylesheet" href="{{ asset('pattern/fuzzy-duck-40/style.css') }}?v=3">
    <link rel="stylesheet" href="{{ asset('pattern/rare-snake-68/style.css') }}?v=3">
    @livewireStyles
</head>
<body class="bg-slate-200 font-poppins">
    <div class="relative mx-auto min-h-dvh max-w-[430px] bg-main shadow-xl">
        {{ $slot }}
        <livewire:deletion-undo />
        <div id="app-confirm-modal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-black/50 p-5" role="presentation" aria-hidden="true" inert data-confirm-modal>
            <section class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-2xl" role="alertdialog" aria-modal="true" aria-labelledby="app-confirm-title" aria-describedby="app-confirm-message">
                <h2 id="app-confirm-title" class="text-lg font-semibold text-dark">{{ __('ui.confirmation') }}</h2>
                <p id="app-confirm-message" class="mt-3 text-sm leading-relaxed text-dark/70"></p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" data-confirm-cancel class="rounded-full bg-light px-5 py-2 text-sm font-medium text-dark">{{ __('ui.cancel') }}</button>
                    <button type="button" data-confirm-accept class="rounded-full bg-red-600 px-5 py-2 text-sm font-medium text-white">{{ __('ui.confirm') }}</button>
                </div>
            </section>
        </div>
        <div id="offline-sync-status" class="fixed top-20 left-1/2 z-40 hidden w-[min(390px,calc(100vw-32px))] -translate-x-1/2 rounded-xl bg-white p-3 text-xs text-dark shadow-lg" role="status"></div>
        <button id="pwa-install-button" type="button" class="fixed right-4 top-3 z-40 hidden rounded-full bg-dark px-4 py-2 text-xs font-medium text-mint shadow">{{ __('ui.install_app') }}</button>
        @auth
        @if ($pushPublicKey = app(\App\Support\WebPushSettings::class)->publicKey())
        <div data-push-first-prompt hidden class="fixed inset-0 z-[90] flex items-end justify-center bg-black/50 p-4 sm:items-center" role="dialog" aria-modal="true" aria-labelledby="push-first-title">
            <section class="w-full max-w-sm rounded-3xl bg-white p-6 text-dark shadow-2xl">
                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-emerald-100 text-2xl" aria-hidden="true">🔔</div>
                <h2 id="push-first-title" class="mt-4 text-center text-lg font-semibold">{{ __('ui.push_prompt_title') }}</h2>
                <p class="mt-2 text-center text-sm leading-relaxed text-dark/70">{{ __('ui.push_prompt_message') }}</p>
                <div class="mt-6 grid grid-cols-2 gap-3">
                    <button type="button" data-push-prompt-dismiss class="rounded-full bg-light px-4 py-3 text-sm font-medium">{{ __('ui.push_prompt_later') }}</button>
                    <button type="button" data-enable-notifications data-push-first-allow data-user-id="{{ auth()->id() }}" data-vapid-key="{{ $pushPublicKey }}" data-subscription-url="{{ route('notifications.push-subscription.store') }}" data-granted-label="{{ __('ui.notifications_allowed') }}" data-denied-label="{{ __('ui.notifications_blocked') }}" data-enable-label="{{ __('ui.enable_notifications') }}" data-error-label="{{ __('ui.notifications_setup_failed') }}" class="rounded-full bg-main px-4 py-3 text-sm font-semibold">{{ __('ui.push_prompt_allow') }}</button>
                </div>
            </section>
        </div>
        @endif
        <div data-voice-call-manager wire:ignore data-incoming-url="{{ route('chat.calls.incoming') }}" data-csrf="{{ csrf_token() }}"
            data-label-calling="{{ __('ui.chat_call_calling') }}" data-label-connecting="{{ __('ui.chat_call_connecting') }}"
            data-label-active="{{ __('ui.chat_call_active') }}" data-label-permission="{{ __('ui.chat_call_permission') }}"
            data-label-failed="{{ __('ui.chat_call_failed') }}" data-label-ended="{{ __('ui.chat_call_ended') }}"
            data-label-incoming="{{ __('ui.chat_call_incoming') }}" data-label-missed="{{ __('ui.chat_call_missed') }}">
            <div data-call-overlay hidden role="dialog" aria-modal="true" aria-label="{{ __('ui.chat_call_title') }}" class="fixed inset-0 z-[120] flex items-center justify-center bg-black/50 p-5">
                <section class="w-full max-w-sm rounded-3xl bg-white p-6 text-center shadow-2xl">
                    <div class="mx-auto grid size-20 place-items-center rounded-full bg-sky/15 text-4xl text-nav">☎</div>
                    <h2 data-call-title class="mt-4 text-lg font-semibold text-dark">{{ __('ui.chat_call_title') }}</h2>
                    <p data-call-status aria-live="polite" class="mt-2 text-sm text-dark/60"></p>
                    <audio data-call-remote autoplay playsinline></audio>
                    <div data-call-incoming-actions hidden class="mt-6 flex justify-center gap-3">
                        <button type="button" data-call-reject class="rounded-full bg-red-600 px-5 py-3 font-medium text-white">{{ __('ui.chat_call_reject') }}</button>
                        <button type="button" data-call-accept class="rounded-full bg-emerald-600 px-5 py-3 font-medium text-white">{{ __('ui.chat_call_accept') }}</button>
                    </div>
                    <div data-call-active-actions hidden class="mt-6 flex justify-center gap-3">
                        <button type="button" data-call-mute aria-pressed="false" class="rounded-full bg-light px-5 py-3 text-sm font-medium text-dark">{{ __('ui.chat_call_mute') }}</button>
                        <button type="button" data-call-end class="rounded-full bg-red-600 px-5 py-3 font-medium text-white">{{ __('ui.chat_call_end') }}</button>
                    </div>
                </section>
            </div>
        </div>
        @endauth

        @unless(request()->routeIs('chat.conversation'))
        <nav id="bottom-navigation" wire:transition.navigate="bottom-navigation" class="fixed inset-x-0 bottom-0 z-40 mx-auto flex h-[88px] max-w-[430px] items-center justify-around gap-1.5 rounded-t-[36px] bg-light px-3 shadow-[0_-8px_30px_rgba(20,50,45,.12)]">
            @foreach ([['dashboard', 'home', __('ui.home')], ['analysis', 'analysis', __('ui.analysis')], ['transactions', 'transactions', __('ui.transactions')], ['savings-goals', 'savings', __('ui.savings')], ['profile', 'profile', __('ui.profile')]] as [$route, $icon, $label])
                <a href="{{ route($route) }}" wire:navigate
                   data-bottom-nav-item="{{ $loop->index }}" data-bottom-nav-route="{{ $route }}" aria-current="{{ request()->routeIs($route) ? 'page' : 'false' }}" aria-label="{{ $label }}" class="flex h-[58px] min-w-14 flex-1 flex-col items-center justify-center gap-1 rounded-[18px] px-1 text-nav {{ request()->routeIs($route) ? 'bg-main text-dark' : '' }}">
                    <x-icon :name="$icon" class="size-5" />
                    <span class="text-[10px] font-medium leading-none">{{ $label }}</span>
                </a>
            @endforeach
        </nav>
        @endunless
    </div>
    @livewireScripts
    <script src="{{ asset('offline.js') }}" defer data-navigate-once></script>
    @stack('scripts')
</body>
</html>
