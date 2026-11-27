<section class="space-y-3" x-data="{ open: @entangle('open') }">
    <div class="flex items-center justify-between">
        <h2 class="font-semibold">{{ __('ui.my_accounts') }}</h2>
        <button type="button" wire:click="create" aria-label="{{ __('ui.add_account') }}" title="{{ __('ui.add_account') }}" class="dark-contrast-action inline-flex items-center gap-1.5 rounded-full bg-main px-3 py-2 text-xs font-medium text-ink"><x-icon name="plus" class="size-3.5" /><span>{{ __('ui.accounts') }}</span></button>
    </div>
    @error('accountId') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
    <div class="space-y-2">
        @forelse ($accounts as $account)
            <article wire:key="account-{{ $account->id }}" class="card-3d flex items-center justify-between rounded-2xl bg-white/70 p-4">
                <div>
                    <p class="font-medium text-dark">{{ $account->name }}</p>
                    <p class="text-xs text-dark/60">{{ $account->type->label() }}</p>
                    <p data-fit-amount class="mt-1 text-sm font-semibold text-dark">{{ \App\Support\Money::rp($account->balance) }}</p>
                </div>
                <div class="flex gap-2"><button type="button" wire:click="edit({{ $account->id }})" aria-label="{{ __('ui.edit') }} {{ $account->name }}" title="{{ __('ui.edit') }}" class="grid size-8 place-items-center rounded-full bg-light text-blue"><x-icon name="edit" class="size-4" /></button><button type="button" wire:click="delete({{ $account->id }})" wire:loading.attr="disabled" wire:target="delete" data-confirm="{{ __('ui.delete_account_confirm') }}" aria-label="{{ __('ui.delete') }} {{ $account->name }}" title="{{ __('ui.delete') }}" class="grid size-8 place-items-center rounded-full bg-red-50 text-red-600 disabled:opacity-50"><x-icon name="trash" class="size-4" /></button></div>
            </article>
        @empty
            <p class="text-sm text-dark/60">{{ __('ui.no_accounts') }}</p>
        @endforelse
    </div>

    <div x-show="open" x-cloak class="fixed inset-0 z-40 grid place-items-center bg-black/50 px-6" x-on:keydown.escape.window="open = false">
        <form wire:submit="save" x-on:click.outside="open = false" class="card-3d w-full max-w-[339px] rounded-[20px] bg-white p-7">
            <h2 class="text-center text-xl font-bold text-dark">{{ $accountId ? __('ui.edit_account') : __('ui.new_account') }}</h2>
            <label for="account-name" class="label">{{ __('ui.account_name') }}</label><input id="account-name" name="name" autocomplete="off" wire:model="name" placeholder="BCA" class="field">
            @error('name') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            <label for="account-type" class="label">{{ __('ui.account_type') }}</label>
            <select id="account-type" name="type" autocomplete="off" wire:model="type" class="field"><option value="cash">{{ __('ui.cash') }}</option><option value="bank">{{ __('ui.bank') }}</option><option value="ewallet">{{ __('ui.ewallet') }}</option></select>
            <label for="account-opening-balance" class="label">{{ __('ui.opening_balance') }}</label><x-money-input id="account-opening-balance" model="openingBalance" :value="$openingBalance" name="opening_balance" placeholder="0" class="field" />
            @error('openingBalance') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            <div class="mt-6 flex flex-col items-center gap-3"><button class="btn inline-flex items-center justify-center gap-2 w-full max-w-[218px]"><span wire:loading wire:target="save" class="app-spinner"></span>{{ __('ui.save') }}</button><button type="button" x-on:click="open = false" class="btn-soft w-full max-w-[218px]">{{ __('ui.cancel') }}</button></div>
        </form>
    </div>
</section>
