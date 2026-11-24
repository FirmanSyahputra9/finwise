<x-screen title="Analisis">
        <x-slot:top>
            <x-summary :balance="$balance" :income="$income" :expense="$expense" />
        </x-slot:top>

        <div class="flex gap-1 rounded-[22px] bg-light p-1.5">
                @foreach (['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'yearly' => 'Tahunan'] as $key => $label)
                <button wire:click="$set('period', '{{ $key }}')"
                        class="h-[50px] flex-1 rounded-[19px] text-[15px] text-nav {{ $period === $key ? 'bg-main font-medium' : '' }}">{{ $label }}</button>
            @endforeach
        </div>

        <details class="group relative mt-4">
            <summary class="flex cursor-pointer list-none items-center justify-between rounded-2xl bg-white/70 px-4 py-3 text-sm font-medium text-ink shadow-sm">
                <span>Filter kategori <span class="font-normal text-dark/60">({{ count($analysisCategoryIds) ? count($analysisCategoryIds).' dipilih' : 'semua kategori' }})</span></span>
                <span class="text-dark/60 transition group-open:rotate-180">⌄</span>
            </summary>
            <div class="mt-2 max-h-64 overflow-y-auto rounded-2xl border border-dark/10 bg-white p-2 shadow-sm">
                @forelse ($analysisCategories as $category)
                    @php($active = in_array($category->id, array_map('intval', $analysisCategoryIds), true))
                    <button type="button" wire:key="analysis-category-{{ $category->id }}" wire:click="toggleAnalysisCategory({{ $category->id }})" aria-pressed="{{ $active ? 'true' : 'false' }}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm hover:bg-light">
                        <span class="grid size-5 place-items-center rounded border {{ $active ? 'border-dark bg-dark text-white' : 'border-dark/30' }}">{{ $active ? '✓' : '' }}</span>
                        <span class="min-w-0 flex-1 truncate text-ink">{{ $category->name }}</span>
                        <span class="text-xs text-dark/50">{{ $category->type->value === 'income' ? 'Masuk' : 'Keluar' }}</span>
                    </button>
                @empty
                    <p class="p-3 text-sm text-dark/60">Belum ada kategori.</p>
                @endforelse
                @if ($analysisCategoryIds)
                    <button type="button" wire:click="$set('analysisCategoryIds', [])" class="mt-1 w-full border-t border-dark/10 px-3 py-2 text-left text-xs font-medium text-blue">Hapus semua pilihan</button>
                @endif
            </div>
        </details>

        <div class="mt-5 rounded-[50px] bg-light px-7 py-6">
            <p class="text-[15px] font-medium text-ink">Pemasukan &amp; Pengeluaran</p>
            <div class="mt-5 flex h-[132px] items-end justify-around gap-2 border-b border-dark px-2">
                @foreach ($rows as $r)
                    <div class="flex h-full min-w-0 flex-1 items-end justify-center gap-1" title="{{ $r['label'] }} · Masuk Rp {{ number_format($r['income'], 0, ',', '.') }} · Keluar Rp {{ number_format($r['expense'], 0, ',', '.') }}">
                        <span class="w-2 flex-1 max-w-4 rounded-t-full bg-main" style="height: {{ $r['income'] ? max(4, (int) round($r['income'] / $max * 132)) : 0 }}px"></span>
                        <span class="w-2 flex-1 max-w-4 rounded-t-full bg-ocean" style="height: {{ $r['expense'] ? max(4, (int) round($r['expense'] / $max * 132)) : 0 }}px"></span>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-between px-1 pt-1.5 font-spartan text-sm text-dark">
                @foreach ($rows as $r) <span>{{ $r['label'] }}</span> @endforeach
            </div>

            <div class="mt-5 grid grid-cols-2 text-center">
                <div>
                    <p class="text-[15px] font-medium text-ink"><span class="mr-1 inline-block size-2 rounded-full bg-main"></span>Pemasukan</p>
                    <p class="text-lg font-semibold text-ink">{{ \App\Support\Money::rp($totalIncome) }}</p>
                </div>
                <div>
                    <p class="text-[15px] font-medium text-ink"><span class="mr-1 inline-block size-2 rounded-full bg-ocean"></span>Pengeluaran</p>
                    <p class="text-lg font-semibold text-ocean">{{ \App\Support\Money::rp($totalExpense) }}</p>
                </div>
            </div>
        </div>

        <div class="mb-3 mt-6 flex items-center justify-between">
            <h2 class="text-[15px] font-medium text-ink">Budget Bulan Ini</h2>
            <button type="button" wire:click="createBudget" class="rounded-full bg-main px-4 py-2 text-xs font-medium text-ink">+ Tambah budget</button>
        </div>
        <form wire:submit="saveMonthlyBudget" class="mb-4 rounded-2xl bg-light p-4">
            <label for="monthly-budget" class="label mt-0">Batas total budget bulan ini</label>
            <div class="flex gap-2"><x-money-input id="monthly-budget" model="monthlyBudget" :value="$monthlyBudget" name="monthly_budget" placeholder="Contoh: 1500000" class="field" /><button class="btn shrink-0 px-5">Simpan</button></div>
            @error('monthlyBudget')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            @if ($monthlyBudget)
                <p class="mt-2 text-xs text-dark/70">Alokasi kategori: {{ \App\Support\Money::rp($allocatedBudget) }} dari {{ \App\Support\Money::rp($monthlyBudget) }}</p>
                <p class="mt-1 text-xs text-dark/70">Sudah dipakai: {{ \App\Support\Money::rp($expense) }} · Sisa yang boleh dipakai bulan ini: {{ \App\Support\Money::rp(max(0, $monthlyBudget - $expense)) }}</p>
            @endif
        </form>
        <div class="grid grid-cols-2 gap-4">
            @if ($budgetCards->isEmpty())
                <p class="col-span-2 text-center text-sm text-nav/60">Belum ada budget bulan ini.</p>
            @endif
            @foreach ($budgetCards as $card)
                <div wire:key="budget-{{ $card['id'] }}" class="relative flex h-[190px] flex-col items-center justify-center gap-2 rounded-[36px] bg-sky px-2">
                    <div class="absolute right-3 top-3 flex gap-2 text-xs font-medium text-white">
                        <button type="button" wire:click="editBudget({{ $card['id'] }})">Ubah</button>
                        <button type="button" wire:click="deleteBudget({{ $card['id'] }})" wire:confirm="Hapus budget kategori ini?">Hapus</button>
                    </div>
                    <div class="grid size-[96px] place-items-center rounded-full" style="background: conic-gradient(#0068FF {{ $card['percentage'] }}%, #F1FFF3 0)">
                        <div class="grid size-[74px] place-items-center rounded-full bg-sky text-xl font-semibold text-mint">{{ $card['percentage'] }}%</div>
                    </div>
                    <p class="truncate text-[15px] font-medium text-mint">{{ $card['name'] }}</p>
                    <p class="text-center text-[10px] text-mint/90">{{ \App\Support\Money::rp($card['spent']) }} / {{ \App\Support\Money::rp($card['amount']) }}</p>
                </div>
            @endforeach
        </div>
    <div x-data="{ open: @entangle('budgetModal') }" x-show="open" x-cloak class="fixed inset-0 z-40 grid place-items-center bg-black/50 px-6" x-on:keydown.escape.window="open = false">
        <form wire:submit="saveBudget" x-on:click.outside="open = false" class="w-full max-w-[339px] rounded-[20px] bg-white p-7">
            <h2 class="text-center text-xl font-bold text-dark">{{ $budgetId ? 'Ubah Budget' : 'Budget Baru' }}</h2>
            <label for="budget-category" class="label">Kategori pengeluaran</label>
            <select id="budget-category" name="category_id" autocomplete="off" wire:model="categoryId" class="field">
                <option value="">Pilih kategori</option>
                @foreach ($expenseCategories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            @error('categoryId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            <label for="budget-amount" class="label">Batas budget (Rp)</label>
            <x-money-input id="budget-amount" model="budgetAmount" :value="$budgetAmount" name="amount" placeholder="Contoh: 1000000" class="field" />
            @error('budgetAmount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            <div class="mt-6 flex flex-col items-center gap-3"><button class="btn w-full max-w-[218px]">Simpan</button><button type="button" x-on:click="open = false" class="btn-soft w-full max-w-[218px]">Batal</button></div>
        </form>
    </div>
</x-screen>
