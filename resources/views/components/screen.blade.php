@props(['title' => null, 'subtitle' => null, 'left' => false, 'variant' => 'default'])
<div data-screen-scroll data-screen-variant="{{ $variant }}" class="flex min-h-dvh min-w-0 flex-col">
    <header data-screen-header class="app-screen-header sticky top-0 z-30 bg-main px-5 pt-12 sm:px-9 sm:pt-14">
        @if (isset($header))
            {{ $header }}
        @elseif ($title)
            <div data-screen-title class="relative flex min-h-8 items-center justify-between gap-3">
                <div class="min-w-0 text-left">
                    <h1 class="truncate text-lg font-semibold leading-tight text-white">{{ $title }}</h1>
                    @if ($subtitle)
                        <p class="truncate font-spartan text-sm text-white/80">{{ $subtitle }}</p>
                    @endif
                </div>
                <livewire:notifications-menu :key="'screen-notifications'" />
            </div>
        @endif
    </header>
    <div data-screen-hero class="{{ $variant === 'wallet' ? 'wallet-screen-hero' : '' }}">{{ $top ?? '' }}</div>

    <main wire:transition.navigate class="mt-6 min-w-0 flex-1 rounded-t-[50px] bg-mint px-5 pb-32 pt-7 sm:px-9 sm:pt-8 {{ $variant === 'wallet' ? 'wallet-screen-panel' : '' }}">
        {{ $slot }}
    </main>
</div>
