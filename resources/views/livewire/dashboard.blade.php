@php($rp = fn ($n) => 'IDR '.number_format($n, 0, ',', '.'))
<x-screen variant="wallet">
    <script type="application/json" id="offline-finance-data">{!! $offlineDataJson !!}</script>
    <x-slot:header>
        <div class="wallet-header-row flex min-h-7 items-center justify-between gap-2">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ route('profile') }}" wire:navigate aria-label="{{ __('ui.profile') }}" class="grid size-7 shrink-0 place-items-center overflow-hidden rounded-full bg-white/20 text-xs font-semibold text-white ring-2 ring-white/50">
                    @if (auth()->user()->profile_photo_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->profile_photo_path) }}" alt="" class="h-full w-full object-cover">
                    @else
                        {{ auth()->user()->initials() }}
                    @endif
                </a>
                <div class="min-w-0 text-left text-white">
                    <p data-balance-value data-fit-amount class="truncate text-base font-bold leading-tight tracking-tight">{{ $rp($totalBalance) }}</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <x-balance-visibility-toggle />
                <livewire:notifications-menu :key="'dashboard-notifications'" />
            </div>
        </div>
    </x-slot:header>
    <x-slot:top>
        <div class="relative min-h-[320px] px-5 pb-12 pt-24">
            <svg id="dashboard-cat" data-sound="{{ asset('storage/sound/cat/cat001.mp3') }}" viewBox="0 0 240 220" role="button" tabindex="0" aria-label="Ajak kucing bermain" title="Gerakkan pointer untuk mengarahkan matanya, klik untuk bermain" class="absolute -top-1 right-5 z-10 h-36 w-auto cursor-pointer touch-manipulation sm:right-10 sm:h-40">
                <defs>
                    <radialGradient id="dashboard-fur" cx="34%" cy="24%" r="78%"><stop offset="0%" stop-color="var(--cat-highlight)" /><stop offset="58%" stop-color="var(--cat-coat)" /><stop offset="100%" stop-color="var(--cat-shadow)" /></radialGradient>
                    <linearGradient id="dashboard-tail" x1="0" y1="0" x2="1" y2="1"><stop stop-color="var(--cat-highlight)" /><stop offset="100%" stop-color="var(--cat-shadow)" /></linearGradient>
                    <linearGradient id="dashboard-ear" x1="0" y1="0" x2="1" y2="1"><stop stop-color="var(--cat-ear-light)" /><stop offset="100%" stop-color="var(--cat-ear-color)" /></linearGradient>
                    <radialGradient id="dashboard-ball" cx="30%" cy="25%" r="75%"><stop stop-color="var(--cat-ball-highlight)" /><stop offset="100%" stop-color="var(--cat-ball-color)" /></radialGradient>
                </defs>
                <ellipse cx="120" cy="206" rx="100" ry="7" fill="#000" opacity=".1" />
                <path class="cat-tail cat-coat-stroke" d="M150 150 C200 150 205 90 178 74" stroke-width="14" stroke-linecap="round" fill="none" />
                <ellipse cx="120" cy="140" rx="44" ry="40" class="cat-coat" />
                <ellipse cx="100" cy="172" rx="12" ry="7" class="cat-shade" />
                <ellipse cx="140" cy="172" rx="12" ry="7" class="cat-shade" />
                <g class="cat-twitch"><polygon points="88,62 86,28 112,46" class="cat-coat" /><polygon points="91,56 90,38 105,46" class="cat-ear" /></g>
                <polygon points="152,62 154,28 128,46" class="cat-coat" />
                <polygon points="149,56 150,38 135,46" class="cat-ear" />
                <circle cx="120" cy="82" r="36" class="cat-coat" />
                <g class="cat-blink"><ellipse cx="105" cy="80" rx="9" ry="10" fill="#FFD84D" /><ellipse cx="135" cy="80" rx="9" ry="10" fill="#FFD84D" /><g class="cat-look"><g class="cat-pupils"><ellipse cx="105" cy="80" rx="3.2" ry="8" fill="#111" /><ellipse cx="135" cy="80" rx="3.2" ry="8" fill="#111" /></g></g></g>
                <path d="M116 93 h8 l-4 5 z" fill="#F7A8B8" />
                <path class="cat-mouth-closed" d="M120 98 q-5 5 -10 1 M120 98 q5 5 10 1" stroke="#aaa" stroke-width="1.8" stroke-linecap="round" fill="none" />
                <g class="cat-mouth-open"><ellipse cx="120" cy="102" rx="6" ry="5" fill="#4a2630" /><path d="M116 104 q4 -3 8 0 q-4 5 -8 0" fill="#f58b9d" /></g>
                <path d="M94 95 l-22 -3 M94 99 l-22 4 M146 95 l22 -3 M146 99 l22 4" stroke="#ddd" stroke-width="1.5" stroke-linecap="round" opacity=".85" />
                <g transform="translate(120 190)"><g class="cat-ball-x"><g class="cat-ball-y"><g class="cat-ball-spin"><circle r="11" class="cat-ball-fill" /><path d="M-9 -4 q9 6 18 0 M-10 3 q10 6 20 0 M-4 -10 q6 10 0 20" class="cat-ball-thread" stroke-width="1.8" stroke-linecap="round" fill="none" /><ellipse cx="-3" cy="-6" rx="3" ry="1.5" fill="white" opacity=".35" /></g></g></g></g>
            </svg>
            <div class="wallet-balance-card card-3d rounded-[26px] bg-white p-5 text-dark shadow-xl">
                <p class="text-sm text-nav/65">{{ __('ui.total_balance') }}</p>
                <p data-balance-value data-fit-amount class="mt-1 text-3xl font-bold tracking-tight text-nav">{{ $rp($totalBalance) }}</p>
                <div class="mt-4 grid grid-cols-2 gap-4 border-t border-dark/10 pt-3 text-sm">
                    <div class="min-w-0"><p class="text-nav/60">{{ __('ui.income') }}</p><p data-fit-amount class="font-semibold text-emerald-700">{{ $rp($income) }}</p></div>
                    <div class="min-w-0"><p class="text-nav/60">{{ __('ui.expense') }}</p><p data-fit-amount class="font-semibold text-rose-600">{{ $rp($expense) }}</p></div>
                </div>
            </div>
        </div>
    </x-slot:top>

<div class="space-y-6">

    <section>
        <h2 class="mb-3 font-semibold">{{ __('ui.quick_access') }}</h2>
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('chat') }}" wire:navigate class="card-3d flex items-center gap-3 rounded-2xl bg-white p-4 text-sm font-medium text-dark"><x-icon name="chat" class="size-5 text-nav" />{{ __('ui.chat') }}</a>
            <a href="{{ route('categories') }}" wire:navigate class="card-3d flex items-center gap-3 rounded-2xl bg-white p-4 text-sm font-medium text-dark"><x-icon name="category" class="size-5 text-nav" />{{ __('ui.categories') }}</a>
        </div>
    </section>

    <livewire:account-manager />

    <section>
        <h2 class="mb-2 font-semibold">{{ __('ui.monthly_budget') }}</h2>
        @if ($monthlyBudget)
            <div class="card-3d mb-4 rounded-2xl bg-dark p-4 text-mint">
                <div class="flex justify-between gap-2 text-sm"><span>{{ __('ui.budget_total') }}</span><strong data-fit-amount class="max-w-[60%] text-right">{{ $rp($monthlyBudget) }}</strong></div>
                <p data-fit-amount class="mt-1 text-xs opacity-80">{{ __('ui.spent') }} {{ $rp($expense) }}</p>
                <p data-fit-amount class="mt-1 text-xs opacity-80">{{ __('ui.remaining_budget') }} {{ $rp($monthlyBudget - $expense) }}</p>
            </div>
        @endif
        @forelse ($budgets as $budget)
            @php($spent = $budget->spent())
            @php($pct = $budget->amount > 0 ? min(100, round($spent / $budget->amount * 100)) : 0)
            <div class="mb-3">
                <div class="flex justify-between text-sm">
                    <span>{{ $budget->category->name }}</span>
                    <span data-fit-amount class="max-w-[58%] text-right">{{ $rp($spent) }} / {{ $rp($budget->amount) }}</span>
                </div>
                <div class="h-2 rounded bg-gray-200">
                    <div class="h-2 rounded {{ $pct >= 100 ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ $pct }}%"></div>
                </div>
            </div>
        @empty
            <p class="text-sm text-nav/60">{{ __('ui.no_budget') }}</p>
        @endforelse
    </section>

    <section>
        <div class="mb-2 flex justify-between">
            <h2 class="font-semibold">{{ __('ui.latest_transactions') }}</h2>
            <a href="{{ route('transactions') }}" wire:navigate class="text-sm text-emerald-600">{{ __('ui.see_all') }}</a>
        </div>
        <ul class="card-3d divide-y divide-dark/10 rounded-2xl bg-white/70">
            @forelse ($recent as $t)
                <li class="flex justify-between p-3 text-sm">
                    <div>
                        <p class="font-medium">{{ $t->category?->name ?? $t->type->label() }}</p>
                        <p class="text-gray-500"><x-date-label :date="$t->date" /> · {{ $t->account->name }}</p>
                    </div>
                    <p data-fit-amount class="max-w-[48%] text-right {{ $t->type->value === 'income' ? 'text-emerald-600' : 'text-red-500' }}">
                        {{ $t->type->value === 'income' ? '+' : '-' }}{{ $rp($t->amount) }}
                    </p>
                </li>
            @empty
                <li class="p-3 text-sm text-gray-500">{{ __('ui.no_transactions') }}</li>
            @endforelse
        </ul>
        <a href="{{ route('transactions') }}" wire:navigate aria-label="{{ __('ui.add_transaction') }}" class="btn mt-4 flex items-center justify-center gap-2"><x-icon name="plus" class="size-4" />{{ __('ui.transactions') }}</a>
    </section>
</div>
</x-screen>
