<div>
    <x-screen title="Analysis">
        <x-slot:top>
            <x-summary :balance="$balance" :income="$income" :expense="$expense" />
        </x-slot:top>

        <div class="flex gap-1 rounded-[22px] bg-light p-1.5">
            @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Year'] as $key => $label)
                <button wire:click="$set('period', '{{ $key }}')"
                        class="h-[50px] flex-1 rounded-[19px] text-[15px] text-nav {{ $period === $key ? 'bg-main font-medium' : '' }}">{{ $label }}</button>
            @endforeach
        </div>

        <div class="mt-5 rounded-[50px] bg-light px-7 py-6">
            <p class="text-[15px] font-medium text-ink">Income &amp; Expenses</p>
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
                    <p class="text-[15px] font-medium text-ink"><span class="mr-1 inline-block size-2 rounded-full bg-main"></span>Income</p>
                    <p class="text-lg font-semibold text-ink">{{ \App\Support\Money::rp($totalIncome) }}</p>
                </div>
                <div>
                    <p class="text-[15px] font-medium text-ink"><span class="mr-1 inline-block size-2 rounded-full bg-ocean"></span>Expense</p>
                    <p class="text-lg font-semibold text-ocean">{{ \App\Support\Money::rp($totalExpense) }}</p>
                </div>
            </div>
        </div>

        <h2 class="mb-3 mt-6 text-[15px] font-medium text-ink">My Targets</h2>
        <div class="grid grid-cols-2 gap-4">
            @forelse ($targets as $b)
                @php $pct = $b->amount > 0 ? min(100, (int) round($b->spent() / $b->amount * 100)) : 0; @endphp
                <div class="flex h-[167px] flex-col items-center justify-center gap-2 rounded-[50px] bg-sky">
                    <div class="grid size-[96px] place-items-center rounded-full" style="background: conic-gradient(var(--color-ocean) {{ $pct }}%, #F1FFF3 0)">
                        <div class="grid size-[74px] place-items-center rounded-full bg-sky text-xl font-semibold text-mint">{{ $pct }}%</div>
                    </div>
                    <p class="text-[15px] font-medium text-mint">{{ $b->category->name }}</p>
                </div>
            @empty
                <p class="col-span-2 text-center text-sm text-nav/60">Belum ada target (budget) bulan ini.</p>
            @endforelse
        </div>
    </x-screen>
</div>
