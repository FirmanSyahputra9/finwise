<div x-data="{ open: false }" x-on:saved.window="open = false">
    <x-screen :title="__('ui.categories')">
        <x-slot:top>
            <x-summary :balance="$balance" :income="$income" :expense="$expense" />
        </x-slot:top>

        <div class="grid grid-cols-3 gap-x-4 gap-y-6">
            @foreach ($categories as $c)
                <div wire:key="c-{{ $c->id }}" class="group relative text-center">
                    <button type="button" wire:click="delete({{ $c->id }})" wire:confirm="{{ __('ui.delete_category_confirm', ['category' => $c->name]) }}" aria-label="{{ __('ui.delete') }} {{ $c->name }}" class="absolute -right-2 -top-2 z-10 grid size-7 place-items-center rounded-full bg-red-500 text-lg leading-none text-white shadow">&times;</button>
                    <button type="button" wire:click="$set('selectedCategory', {{ $selectedCategory === $c->id ? 'null' : $c->id }})" class="w-full grid h-[98px] place-items-center rounded-[26px] text-4xl font-semibold text-mint {{ $selectedCategory === $c->id ? 'bg-dark' : ($loop->first ? 'bg-ocean' : 'bg-sky') }}">
                        {{ mb_strtoupper(mb_substr($c->name, 0, 1)) }}
                    </button>
                    <p class="mt-2 truncate text-[15px] font-medium text-ink">{{ $c->name }}</p>
                </div>
            @endforeach

            <button type="button" x-on:click="open = true" class="text-center">
                <div class="grid h-[98px] place-items-center rounded-[26px] bg-sky text-mint"><x-icon name="plus" class="size-10" /></div>
                <p class="mt-2 text-[15px] font-medium text-ink">{{ __('ui.add_category') }}</p>
            </button>
        </div>

        @if ($selectedCategory)
            <section class="mt-6 rounded-2xl bg-white/70 p-4">
                <h2 class="font-semibold">{{ __('ui.transactions_for') }} {{ $selectedCategoryName }}</h2>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <select wire:model.live="typeFilter" class="transaction-control text-sm"><option value="">{{ __('ui.all_types') }}</option><option value="income">{{ __('ui.income') }}</option><option value="expense">{{ __('ui.expense') }}</option></select>
                    <span></span><input type="date" wire:model.live="dateFrom" class="transaction-control text-sm" aria-label="{{ __('ui.from_date') }}"><input type="date" wire:model.live="dateTo" class="transaction-control text-sm" aria-label="{{ __('ui.to_date') }}">
                </div>
                <ul class="mt-3 divide-y divide-dark/10">@forelse ($transactions as $transaction)<li class="flex justify-between py-2 text-sm"><span>{{ $transaction->date->translatedFormat('d M Y') }} · {{ $transaction->account->name }}</span><strong>{{ $transaction->type->value === 'income' ? '+' : '-' }}{{ \App\Support\Money::rp($transaction->amount) }}</strong></li>@empty<li class="py-3 text-sm text-dark/60">{{ __('ui.no_transactions_filter') }}</li>@endforelse</ul>
            </section>
        @endif
    </x-screen>

    <div x-show="open" x-cloak class="fixed inset-0 z-40 grid place-items-center bg-black/50 px-6" x-on:keydown.escape.window="open = false">
        <form wire:submit="save" x-on:click.outside="open = false" class="w-full max-w-[339px] rounded-[20px] bg-white p-8">
            <h2 class="text-center text-xl font-bold text-dark">{{ __('ui.new_category') }}</h2>
            <input id="category-name" name="name" type="text" autocomplete="off" wire:model="name" placeholder="{{ __('ui.category_name_placeholder') }}" class="field mt-6">
            @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            <div class="mt-6 flex flex-col items-center gap-3">
                <button type="submit" class="btn w-full max-w-[218px]">{{ __('ui.save') }}</button>
                <button type="button" x-on:click="open = false" class="btn-soft w-full max-w-[218px]">{{ __('ui.cancel') }}</button>
            </div>
        </form>
    </div>
</div>
