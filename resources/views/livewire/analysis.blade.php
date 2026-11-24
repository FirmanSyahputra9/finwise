<div>
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

        <div class="mt-5 rounded-[50px] bg-light px-7 py-6">
            <p class="text-[15px] font-medium text-ink">Pemasukan &amp; Pengeluaran</p>
            <div class="mt-5 flex h-[132px] items-end justify-between border-b border-dark px-2">
                @foreach ($rows as $r)
                    <div class="flex items-end gap-1.5">
                        <span class="w-1.5 rounded-t-full bg-main" style="height: {{ max(3, (int) round($r['income'] / $max * 132)) }}px"></span>
                        <span class="w-1.5 rounded-t-full bg-ocean" style="height: {{ max(3, (int) round($r['expense'] / $max * 132)) }}px"></span>
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
        <div class="grid grid-cols-2 gap-4">
            @forelse ($targets as $b)
                @php $pct = $b->amount > 0 ? min(100, (int) round($b->spent() / $b->amount * 100)) : 0; @endphp
                <div wire:key="budget-{{ $b->id }}" class="relative flex h-[190px] flex-col items-center justify-center gap-2 rounded-[36px] bg-sky px-2">
                    <div class="absolute right-3 top-3 flex gap-2 text-xs font-medium text-white">
                        <button type="button" wire:click="editBudget({{ $b->id }})">Ubah</button>
                        <button type="button" wire:click="deleteBudget({{ $b->id }})" wire:confirm="Hapus budget kategori ini?">Hapus</button>
                    </div>
                    <div class="grid size-[96px] place-items-center rounded-full" style="background: conic-gradient(#0068FF {{ $pct }}%, #F1FFF3 0)">
                        <div class="grid size-[74px] place-items-center rounded-full bg-sky text-xl font-semibold text-mint">{{ $pct }}%</div>
                    </div>
                    <p class="truncate text-[15px] font-medium text-mint">{{ $b->category->name }}</p>
                    <p class="text-center text-[10px] text-mint/90">{{ \App\Support\Money::rp($b->spent()) }} / {{ \App\Support\Money::rp($b->amount) }}</p>
                </div>
            @empty
                <p class="col-span-2 text-center text-sm text-nav/60">Belum ada budget bulan ini.</p>
            @endforelse
        </div>
    </x-screen>

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
            <input id="budget-amount" name="amount" type="number" min="1" autocomplete="off" wire:model="budgetAmount" placeholder="Contoh: 1000000" class="field">
            @error('budgetAmount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            <div class="mt-6 flex flex-col items-center gap-3"><button class="btn w-full max-w-[218px]">Simpan</button><button type="button" x-on:click="open = false" class="btn-soft w-full max-w-[218px]">Batal</button></div>
        </form>
    </div>
</div>
