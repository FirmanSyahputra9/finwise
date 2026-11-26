@php($rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.'))
<div
    x-on:edit-transaction.window="document.getElementById('transaction-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' })">
    <x-screen :title="__('ui.transactions')">
        <div class="space-y-6">
        <script type="application/json" id="offline-finance-data">{!! $offlineDataJson !!}</script>
        <form id="transaction-form" wire:submit="save"
            class="card-3d w-full min-w-0 space-y-4 rounded-[24px] bg-white/70 p-3 min-[380px]:p-4">
            <input id="transaction-type" name="type" type="hidden" autocomplete="off" data-offline-field="type"
                value="{{ $type }}">
            <h2 class="text-base font-semibold text-dark">
                {{ $editingId ? __('ui.edit_transaction') : __('ui.new_transaction') }}</h2>
            <div role="group" aria-label="{{ __('ui.transaction_kind') }}"
                class="segmented-control grid grid-cols-3 gap-1 rounded-[18px] bg-light p-1">
                @foreach (['expense' => __('ui.expense'), 'income' => __('ui.income'), 'transfer' => __('ui.transfer')] as $value => $label)
                    <button type="button" aria-pressed="{{ $type === $value ? 'true' : 'false' }}"
                        data-offline-type="{{ $value }}" wire:click="$set('type', '{{ $value }}')"
                        class="min-h-11 min-w-0 rounded-[14px] px-1 py-2 text-center text-[clamp(0.65rem,3.4vw,0.8rem)] font-medium leading-tight transition {{ $type === $value ? 'bg-main text-ink shadow-sm' : 'bg-transparent text-dark/70' }}">
                        <span
                            class="min-[380px]:hidden">{{ ['expense' => __('ui.out_short'), 'income' => __('ui.in_short'), 'transfer' => __('ui.transfer')][$value] }}</span>
                        <span class="hidden min-[380px]:inline">{{ $label }}</span>
                    </button>
                @endforeach
            </div>

            <label for="transaction-amount"
                class="block text-xs font-medium text-dark/70">{{ __('ui.transaction_amount') }}
                <span
                    class="mt-1 flex min-w-0 items-center gap-2 rounded-[18px] bg-light px-3 min-[380px]:gap-3 min-[380px]:px-4">
                    <span class="shrink-0 text-base font-semibold text-dark/60">Rp</span>
                    <x-money-input id="transaction-amount" model="amount" :value="$amount" name="amount"
                        placeholder="0" class="transaction-amount" offline-field="amount" />
                </span>
            </label>
            @error('amount')
                <p class="text-xs text-red-500">{{ $message }}</p>
            @enderror

            <select id="transaction-account" name="account_id" autocomplete="off" data-offline-field="account_id"
                wire:model="account_id" class="transaction-control">
                @foreach ($accounts as $a)
                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                @endforeach
            </select>
            <a href="{{ route('dashboard') }}" wire:navigate
                class="block text-right text-xs text-blue">{{ __('ui.manage_accounts') }}</a>

            <div id="transaction-to-account-wrap" @if ($type !== 'transfer') hidden @endif>
                <select id="transaction-to-account" name="to_account_id" autocomplete="off"
                    data-offline-field="to_account_id" wire:model="to_account_id" class="transaction-control">
                    <option value="">{{ __('ui.to_account') }}</option>
                    @foreach ($accounts as $a)
                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                    @endforeach
                </select>
                @error('to_account_id')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div id="transaction-category-wrap" @if ($type === 'transfer') hidden @endif>
                <select id="transaction-category" name="category_id" autocomplete="off" data-offline-field="category_id"
                    wire:model="category_id" class="transaction-control">
                    <option value="">{{ __('ui.category') }}</option>
                    @foreach ($allCategories->filter(fn($category) => $category->type->value === $type) as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <input id="transaction-date" name="date" type="date" autocomplete="off" data-offline-field="date"
                wire:model="date" class="transaction-control">
            <input id="transaction-note" name="note" type="text" autocomplete="off" data-offline-field="note"
                wire:model="note" placeholder="{{ __('ui.optional_note') }}" class="transaction-control">

            <button class="btn inline-flex items-center justify-center gap-2 w-full"><span wire:loading
                    wire:target="save"
                    class="app-spinner"></span>{{ $editingId ? __('ui.save_changes') : __('ui.save_transaction') }}</button>
            @if ($editingId)
                <button type="button" wire:click="cancelEdit"
                    class="btn-soft w-full">{{ __('ui.cancel_edit') }}</button>
            @endif
        </form>

        <div
            class="flex min-w-0 flex-col gap-2 min-[380px]:flex-row min-[380px]:items-center min-[380px]:justify-between">
            <h2 class="font-semibold">{{ __('ui.history') }}</h2>
            <select id="transaction-filter-type" name="filter_type" autocomplete="off" wire:model.live="filterType"
                class="transaction-control w-full min-w-0 text-sm min-[380px]:w-auto">
                <option value="">{{ __('ui.all') }}</option>
                @foreach ($filterTypes as $availableType)
                    <option value="{{ $availableType }}">
                        {{ ['income' => __('ui.income'), 'expense' => __('ui.expense'), 'transfer' => __('ui.transfer')][$availableType] ?? ucfirst($availableType) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div
            class="card-3d grid w-full min-w-0 grid-cols-1 gap-2 rounded-2xl bg-white/70 p-2 min-[380px]:p-3 min-[400px]:grid-cols-2">
            <input id="transaction-search" name="search" type="search" autocomplete="off"
                wire:model.live.debounce.300ms="search" placeholder="{{ __('ui.search_transactions') }}"
                class="field min-w-0 min-[400px]:col-span-2">
            <select id="transaction-filter-account" name="filter_account" autocomplete="off"
                wire:model.live="filterAccount" class="transaction-control min-w-0 text-sm">
                <option value="">{{ __('ui.all_accounts') }}</option>
                @foreach ($filterAccounts as $a)
                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                @endforeach
            </select>
            <select id="transaction-filter-category" name="filter_category" autocomplete="off"
                wire:model.live="filterCategory" class="transaction-control min-w-0 text-sm">
                <option value="">{{ __('ui.all_categories_filter') }}</option>
                @foreach ($filterCategories as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
            <label for="transaction-date-from" class="min-w-0 text-xs text-dark/70">{{ __('ui.from') }}<input
                    id="transaction-date-from" name="date_from" type="date" autocomplete="off"
                    wire:model.live="dateFrom" class="transaction-control mt-1 min-w-0 text-sm"></label>
            <label for="transaction-date-to" class="min-w-0 text-xs text-dark/70">{{ __('ui.until') }}<input
                    id="transaction-date-to" name="date_to" type="date" autocomplete="off"
                    wire:model.live="dateTo" class="transaction-control mt-1 min-w-0 text-sm"></label>
            <button type="button" wire:click="resetFilters"
                class="text-right text-xs text-blue min-[400px]:col-span-2">{{ __('ui.reset_filter') }}</button>
        </div>

        <ul class="card-3d divide-y divide-dark/10 rounded-2xl bg-white/70">
            @forelse ($transactions as $t)
                <li class="grid min-w-0 grid-cols-1 items-center gap-2 p-3 text-sm min-[380px]:grid-cols-[minmax(0,1fr)_auto]"
                    wire:key="t-{{ $t->id }}">
                    <div class="min-w-0">
                        <p class="break-words font-medium">
                            {{ $t->category?->name ?? ($t->type->value === 'transfer' ? __('ui.transfer_to') . ' ' . $t->toAccount?->name : $t->type->label()) }}
                        </p>
                        <p class="text-gray-500"><x-date-label :date="$t->date" /> ·
                            {{ $t->account->name }}{{ $t->note ? ' · ' . $t->note : '' }}</p>
                    </div>
                    <div
                        class="flex min-w-0 items-center justify-between gap-2 min-[380px]:flex-col min-[380px]:items-end">
                        <span data-fit-amount
                            class="max-w-[58%] min-w-0 whitespace-nowrap text-xs font-semibold tabular-nums sm:text-sm {{ $t->type->value === 'income' ? 'text-emerald-600' : 'text-red-500' }}">
                            {{ $t->type->value === 'income' ? '+' : '-' }}{{ $rp($t->amount) }}
                        </span>
                        <div class="flex shrink-0 gap-3">
                            <button type="button" wire:click="edit({{ $t->id }})"
                                class="text-xs text-blue">{{ __('ui.edit') }}</button>
                            <button type="button" wire:click="delete({{ $t->id }})"
                                wire:loading.attr="disabled" wire:target="delete"
                                data-confirm="{{ __('ui.delete_transaction_confirm') }}"
                                class="text-xs text-red-600 disabled:opacity-50">{{ __('ui.delete') }}</button>
                        </div>
                    </div>
                </li>
            @empty
                <li class="p-3 text-sm text-gray-500">{{ __('ui.no_transactions') }}</li>
            @endforelse
        </ul>

        {{ $transactions->links() }}
        </div>
    </x-screen>
</div>
