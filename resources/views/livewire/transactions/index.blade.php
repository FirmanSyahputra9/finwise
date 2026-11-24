@php($rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.'))
<div x-on:edit-transaction.window="document.getElementById('transaction-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' })">
<x-screen title="Transaksi">
    <script type="application/json" id="offline-finance-data">{!! $offlineDataJson !!}</script>
    <form id="transaction-form" wire:submit="save" class="min-w-0 space-y-4 rounded-[24px] bg-white/70 p-4 sm:p-5">
        <input id="transaction-type" name="type" type="hidden" autocomplete="off" data-offline-field="type" value="{{ $type }}">
        <h2 class="text-base font-semibold text-dark">{{ $editingId ? 'Ubah transaksi' : 'Transaksi baru' }}</h2>
        <div class="grid grid-cols-3 gap-1 rounded-[18px] bg-light p-1">
            @foreach (['expense' => 'Pengeluaran', 'income' => 'Pemasukan', 'transfer' => 'Transfer'] as $value => $label)
                <button type="button" data-offline-type="{{ $value }}" wire:click="$set('type', '{{ $value }}')"
                    class="min-h-11 min-w-0 rounded-[14px] px-1 py-2 text-center text-[clamp(0.65rem,3.4vw,0.8rem)] font-medium leading-tight transition {{ $type === $value ? 'bg-main text-ink shadow-sm' : 'bg-transparent text-dark/70' }}">
                    <span class="min-[380px]:hidden">{{ ['expense' => 'Keluar', 'income' => 'Masuk', 'transfer' => 'Transfer'][$value] }}</span>
                    <span class="hidden min-[380px]:inline">{{ $label }}</span>
                </button>
            @endforeach
        </div>

        <label for="transaction-amount" class="block text-xs font-medium text-dark/70">Nominal transaksi
            <span class="mt-1 flex min-w-0 items-center gap-3 rounded-[18px] bg-light px-4">
                <span class="shrink-0 text-base font-semibold text-dark/60">Rp</span>
                <input id="transaction-amount" name="amount" type="number" min="1" inputmode="numeric" autocomplete="off" data-offline-field="amount" wire:model="amount" placeholder="0" class="transaction-amount">
            </span>
        </label>
        @error('amount') <p class="text-xs text-red-500">{{ $message }}</p> @enderror

        <select id="transaction-account" name="account_id" autocomplete="off" data-offline-field="account_id" wire:model="account_id" class="transaction-control">
            @foreach ($accounts as $a) <option value="{{ $a->id }}">{{ $a->name }}</option> @endforeach
        </select>
        <a href="{{ route('dashboard') }}" wire:navigate class="block text-right text-xs text-blue">Kelola akun</a>

        <div id="transaction-to-account-wrap" @if ($type !== 'transfer') hidden @endif>
            <select id="transaction-to-account" name="to_account_id" autocomplete="off" data-offline-field="to_account_id" wire:model="to_account_id" class="transaction-control">
                <option value="">Ke akun...</option>
                @foreach ($accounts as $a) <option value="{{ $a->id }}">{{ $a->name }}</option> @endforeach
            </select>
            @error('to_account_id') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div id="transaction-category-wrap" @if ($type === 'transfer') hidden @endif>
            <select id="transaction-category" name="category_id" autocomplete="off" data-offline-field="category_id" wire:model="category_id" class="transaction-control">
                <option value="">Kategori...</option>
                @foreach ($allCategories->filter(fn ($category) => $category->type->value === $type) as $c) <option value="{{ $c->id }}">{{ $c->name }}</option> @endforeach
            </select>
            @error('category_id') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
        </div>

        <input id="transaction-date" name="date" type="date" autocomplete="off" data-offline-field="date" wire:model="date" class="transaction-control">
        <input id="transaction-note" name="note" type="text" autocomplete="off" data-offline-field="note" wire:model="note" placeholder="Catatan (opsional)" class="transaction-control">

        <button class="btn w-full">{{ $editingId ? 'Simpan perubahan' : 'Simpan transaksi' }}</button>
        @if ($editingId)
            <button type="button" wire:click="cancelEdit" class="btn-soft w-full">Batal mengubah</button>
        @endif
    </form>

    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="font-semibold">Riwayat</h2>
        <select id="transaction-filter-type" name="filter_type" autocomplete="off" wire:model.live="filterType" class="transaction-control w-auto text-sm">
            <option value="">Semua</option>
            <option value="income">Pemasukan</option>
            <option value="expense">Pengeluaran</option>
            <option value="transfer">Transfer</option>
        </select>
    </div>

    <div class="grid min-w-0 grid-cols-1 gap-2 rounded-2xl bg-white/70 p-3 min-[380px]:grid-cols-2">
        <input id="transaction-search" name="search" type="search" autocomplete="off" wire:model.live.debounce.300ms="search" placeholder="Cari catatan, kategori, akun..." class="field min-[380px]:col-span-2">
        <select id="transaction-filter-account" name="filter_account" autocomplete="off" wire:model.live="filterAccount" class="transaction-control text-sm"><option value="">Semua akun</option>@foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select>
        <select id="transaction-filter-category" name="filter_category" autocomplete="off" wire:model.live="filterCategory" class="transaction-control text-sm"><option value="">Semua kategori</option>@foreach ($allCategories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
        <label for="transaction-date-from" class="text-xs text-dark/70">Dari<input id="transaction-date-from" name="date_from" type="date" autocomplete="off" wire:model.live="dateFrom" class="transaction-control mt-1 text-sm"></label>
        <label for="transaction-date-to" class="text-xs text-dark/70">Sampai<input id="transaction-date-to" name="date_to" type="date" autocomplete="off" wire:model.live="dateTo" class="transaction-control mt-1 text-sm"></label>
        <button type="button" wire:click="resetFilters" class="col-span-2 text-right text-xs text-blue">Reset filter</button>
    </div>

    <ul class="divide-y divide-dark/10 rounded-2xl bg-white/70">
        @forelse ($transactions as $t)
            <li class="grid min-w-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-2 p-3 text-sm" wire:key="t-{{ $t->id }}">
                <div class="min-w-0">
                    <p class="font-medium">{{ $t->category?->name ?? ($t->type->value === 'transfer' ? 'Transfer ke '.$t->toAccount?->name : $t->type->label()) }}</p>
                    <p class="text-gray-500">{{ $t->date->translatedFormat('d M Y') }} · {{ $t->account->name }}{{ $t->note ? ' · '.$t->note : '' }}</p>
                </div>
                <div class="flex min-w-0 flex-col items-end gap-1">
                    <span class="whitespace-nowrap text-xs font-semibold tabular-nums sm:text-sm {{ $t->type->value === 'income' ? 'text-emerald-600' : 'text-red-500' }}">
                        {{ $t->type->value === 'income' ? '+' : '-' }}{{ $rp($t->amount) }}
                    </span>
                    <div class="flex shrink-0 gap-3">
                        <button type="button" wire:click="edit({{ $t->id }})" class="text-xs text-blue">Ubah</button>
                        <button type="button" wire:click="delete({{ $t->id }})" wire:confirm="Hapus transaksi ini?" class="text-xs text-red-600">Hapus</button>
                    </div>
                </div>
            </li>
        @empty
            <li class="p-3 text-sm text-gray-500">Belum ada transaksi.</li>
        @endforelse
    </ul>

    {{ $transactions->links() }}
</x-screen>
</div>
