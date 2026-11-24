@php($rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.'))
<x-screen title="Beranda">
    <script type="application/json" id="offline-finance-data">{!! $offlineDataJson !!}</script>
    <x-slot:top>
        <div class="mt-5 rounded-[26px] bg-dark p-5 text-mint">
        <p class="text-sm opacity-80">Total Saldo</p>
        <p class="text-3xl font-bold">{{ $rp($totalBalance) }}</p>
        <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
            <div><p class="opacity-80">Pemasukan</p><p class="font-semibold">{{ $rp($income) }}</p></div>
            <div><p class="opacity-80">Pengeluaran</p><p class="font-semibold">{{ $rp($expense) }}</p></div>
        </div>
        </div>
    </x-slot:top>

<div class="space-y-6">

    <livewire:account-manager />

    <section>
        <h2 class="mb-2 font-semibold">Budget bulan ini</h2>
        @if ($monthlyBudget)
            <div class="mb-4 rounded-2xl bg-dark p-4 text-mint">
                <div class="flex justify-between text-sm"><span>Budget total</span><strong>{{ $rp($monthlyBudget) }}</strong></div>
                <p class="mt-1 text-xs opacity-80">Sudah dipakai: {{ $rp($expense) }}</p>
                <p class="mt-1 text-xs opacity-80">Sisa budget yang boleh dipakai: {{ $rp(max(0, $monthlyBudget - $expense)) }}</p>
            </div>
        @endif
        @forelse ($budgets as $budget)
            @php($spent = $budget->spent())
            @php($pct = $budget->amount > 0 ? min(100, round($spent / $budget->amount * 100)) : 0)
            <div class="mb-3">
                <div class="flex justify-between text-sm">
                    <span>{{ $budget->category->name }}</span>
                    <span>{{ $rp($spent) }} / {{ $rp($budget->amount) }}</span>
                </div>
                <div class="h-2 rounded bg-gray-200">
                    <div class="h-2 rounded {{ $pct >= 100 ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ $pct }}%"></div>
                </div>
            </div>
        @empty
            <p class="text-sm text-nav/60">Belum ada budget.</p>
        @endforelse
    </section>

    <section>
        <div class="mb-2 flex justify-between">
            <h2 class="font-semibold">Transaksi terakhir</h2>
            <a href="{{ route('transactions') }}" wire:navigate class="text-sm text-emerald-600">Lihat semua</a>
        </div>
        <ul class="divide-y divide-dark/10 rounded-2xl bg-white/70">
            @forelse ($recent as $t)
                <li class="flex justify-between p-3 text-sm">
                    <div>
                        <p class="font-medium">{{ $t->category?->name ?? $t->type->label() }}</p>
                        <p class="text-gray-500">{{ $t->date->translatedFormat('d M Y') }} · {{ $t->account->name }}</p>
                    </div>
                    <p class="{{ $t->type->value === 'income' ? 'text-emerald-600' : 'text-red-500' }}">
                        {{ $t->type->value === 'income' ? '+' : '-' }}{{ $rp($t->amount) }}
                    </p>
                </li>
            @empty
                <li class="p-3 text-sm text-gray-500">Belum ada transaksi.</li>
            @endforelse
        </ul>
        <a href="{{ route('transactions') }}" wire:navigate class="btn mt-4 flex items-center justify-center">Tambah transaksi</a>
    </section>
</div>
</x-screen>
