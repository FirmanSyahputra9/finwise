@props(['title', 'subtitle' => null, 'left' => false])
<div class="flex min-h-dvh flex-col">
    <header class="px-5 pt-12 sm:px-9 sm:pt-14">
        <div class="relative flex min-h-8 items-center {{ $left ? '' : 'justify-center' }}">
            <div class="{{ $left ? '' : 'text-center' }}">
                <h1 class="text-xl font-semibold leading-tight text-nav">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="font-spartan text-sm text-nav">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        {{ $top ?? '' }}
    </header>

    <main class="mt-6 flex-1 rounded-t-[50px] bg-mint px-5 pb-32 pt-7 sm:px-9 sm:pt-8">
        {{ $slot }}
    </main>
</div>
