<x-screen :title="__('ui.analysis')">
        <x-slot:top>
            <x-summary :balance="$balance" :income="$income" :expense="$expense" />
        </x-slot:top>

        <div role="group" aria-label="{{ __('ui.period') }}" class="segmented-control flex gap-1 rounded-[22px] bg-light p-1.5">
                @foreach (['daily' => __('ui.daily'), 'weekly' => __('ui.weekly'), 'monthly' => __('ui.monthly'), 'yearly' => __('ui.yearly')] as $key => $label)
                <button type="button" aria-pressed="{{ $period === $key ? 'true' : 'false' }}" wire:click="$set('period', '{{ $key }}')"
                        class="h-[50px] flex-1 rounded-[19px] text-[15px] text-nav {{ $period === $key ? 'bg-main font-medium' : '' }}">{{ $label }}</button>
            @endforeach
        </div>

        <details class="group relative mt-4">
            <summary class="card-3d flex cursor-pointer list-none items-center justify-between rounded-2xl bg-white/70 px-4 py-3 text-sm font-medium text-ink">
                <span>{{ __('ui.category_filter') }} <span class="font-normal text-dark/60">({{ count($analysisCategoryIds) ? count($analysisCategoryIds).' '.__('ui.selected') : __('ui.all_categories') }})</span></span>
                <span class="text-dark/60 transition group-open:rotate-180">⌄</span>
            </summary>
            <div class="card-3d mt-2 max-h-64 overflow-y-auto rounded-2xl bg-white p-2">
                @forelse ($analysisCategories as $category)
                    @php($active = in_array($category->id, array_map('intval', $analysisCategoryIds), true))
                    <button type="button" wire:key="analysis-category-{{ $category->id }}" wire:click="toggleAnalysisCategory({{ $category->id }})" aria-pressed="{{ $active ? 'true' : 'false' }}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm hover:bg-light">
                        <span class="grid size-5 place-items-center rounded border {{ $active ? 'border-dark bg-dark text-white' : 'border-dark/30' }}">{{ $active ? '✓' : '' }}</span>
                        <span class="min-w-0 flex-1 truncate text-ink">{{ $category->name }}</span>
                        <span class="text-xs text-dark/50">{{ $category->type->value === 'income' ? __('ui.in') : __('ui.out') }}</span>
                    </button>
                @empty
                    <p class="p-3 text-sm text-dark/60">{{ __('ui.no_categories') }}</p>
                @endforelse
                @if ($analysisCategoryIds)
                    <button type="button" wire:click="$set('analysisCategoryIds', [])" class="mt-1 w-full border-t border-dark/10 px-3 py-2 text-left text-xs font-medium text-blue">{{ __('ui.clear_selection') }}</button>
                @endif
            </div>
        </details>
        @if ($analysisCategoryIds)
            <p class="mt-2 rounded-xl bg-light/70 px-3 py-2 text-xs leading-relaxed text-dark/70">
                {{ __('ui.chart_shows') }} {{ implode(', ', $selectedAnalysisCategories) }}
            </p>
        @endif

        <div class="card-3d mt-5 rounded-[50px] bg-light px-7 py-6">
            <p class="text-[15px] font-medium text-ink">{{ __('ui.income_expense') }}</p>
            <div class="mt-5 flex h-[132px] items-end justify-around gap-2 border-b border-dark px-2">
                @foreach ($rows as $r)
                    <div class="flex h-full min-w-0 flex-1 items-end justify-center gap-1" title="{{ $r['label'] }} · {{ __('ui.in') }} Rp {{ number_format($r['income'], 0, ',', '.') }} · {{ __('ui.out') }} Rp {{ number_format($r['expense'], 0, ',', '.') }}">
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
                    <p class="text-[15px] font-medium text-ink"><span class="mr-1 inline-block size-2 rounded-full bg-main"></span>{{ __('ui.income') }}</p>
                    <p class="text-lg font-semibold text-ink">{{ \App\Support\Money::rp($totalIncome) }}</p>
                </div>
                <div>
                    <p class="text-[15px] font-medium text-ink"><span class="mr-1 inline-block size-2 rounded-full bg-ocean"></span>{{ __('ui.expense') }}</p>
                    <p class="text-lg font-semibold text-ocean">{{ \App\Support\Money::rp($totalExpense) }}</p>
                </div>
            </div>
        </div>

        <div class="mb-3 mt-6 flex items-center justify-between">
            <h2 class="text-[15px] font-medium text-ink">{{ __('ui.monthly_budget') }}</h2>
            <button type="button" wire:click="createBudget" class="rounded-full bg-main px-4 py-2 text-xs font-medium text-ink">{{ __('ui.add_budget') }}</button>
        </div>
        <form wire:submit="saveMonthlyBudget" class="card-3d mb-4 rounded-2xl bg-light p-4">
            <label for="monthly-budget" class="label mt-0">{{ __('ui.monthly_budget_limit') }}</label>
            <div class="flex gap-2"><x-money-input id="monthly-budget" model="monthlyBudget" :value="$monthlyBudget" name="monthly_budget" placeholder="1,500,000" class="field" /><button class="btn inline-flex shrink-0 items-center gap-2 px-5"><span wire:loading wire:target="saveMonthlyBudget" class="app-spinner"></span>{{ __('ui.save') }}</button></div>
            @error('monthlyBudget')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            @if ($monthlyBudget)
                <p class="mt-2 text-xs text-dark/70">{{ __('ui.allocated') }} {{ \App\Support\Money::rp($allocatedBudget) }} {{ __('ui.of') }} {{ \App\Support\Money::rp($monthlyBudget) }}</p>
                <p class="mt-1 text-xs text-dark/70">{{ __('ui.spent') }} {{ \App\Support\Money::rp($expense) }} · {{ __('ui.remaining_month') }} {{ \App\Support\Money::rp($monthlyBudget - $expense) }}</p>
            @endif
        </form>
        <div class="grid grid-cols-2 gap-4">
            @if ($budgetCards->isEmpty())
                <p class="col-span-2 text-center text-sm text-nav/60">{{ __('ui.no_budget') }}</p>
            @endif
            @foreach ($budgetCards as $card)
                <div wire:key="budget-{{ $card['id'] }}" class="card-3d relative flex h-[190px] flex-col items-center justify-center gap-2 rounded-[36px] bg-sky px-2">
                    <div class="absolute right-3 top-3 flex gap-2 text-xs font-medium text-white">
                        <button type="button" wire:click="editBudget({{ $card['id'] }})">{{ __('ui.edit') }}</button>
                        <button type="button" wire:click="deleteBudget({{ $card['id'] }})" wire:loading.attr="disabled" wire:target="deleteBudget" wire:confirm="{{ __('ui.delete_category_budget') }}" class="disabled:opacity-50">{{ __('ui.delete') }}</button>
                    </div>
                    <div class="grid size-[96px] place-items-center rounded-full" style="background: conic-gradient(var(--color-ocean) {{ $card['percentage'] }}%, #F1FFF3 0)">
                        <div class="grid size-[74px] place-items-center rounded-full bg-sky text-xl font-semibold text-mint">{{ $card['percentage'] }}%</div>
                    </div>
                    <p class="truncate text-[15px] font-medium text-mint">{{ $card['name'] }}</p>
                    <p class="text-center text-[10px] text-mint/90">{{ \App\Support\Money::rp($card['spent']) }} / {{ \App\Support\Money::rp($card['amount']) }}</p>
                </div>
            @endforeach
        </div>
    <div x-data="{ open: @entangle('budgetModal') }" x-show="open" x-cloak class="fixed inset-0 z-40 grid place-items-center bg-black/50 px-6" x-on:keydown.escape.window="open = false">
        <form wire:submit="saveBudget" x-on:click.outside="open = false" class="card-3d w-full max-w-[339px] rounded-[20px] bg-white p-7">
            <h2 class="text-center text-xl font-bold text-dark">{{ $budgetId ? __('ui.edit_budget') : __('ui.new_budget') }}</h2>
            <label for="budget-category" class="label">{{ __('ui.expense_category') }}</label>
            <select id="budget-category" name="category_id" autocomplete="off" wire:model="categoryId" class="field">
                <option value="">{{ __('ui.choose_category') }}</option>
                @foreach ($expenseCategories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            @error('categoryId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            <label for="budget-amount" class="label">{{ __('ui.budget_limit_rp') }}</label>
            <x-money-input id="budget-amount" model="budgetAmount" :value="$budgetAmount" name="amount" placeholder="1,000,000" class="field" />
            @error('budgetAmount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            <div class="mt-6 flex flex-col items-center gap-3"><button class="btn inline-flex items-center justify-center gap-2 w-full max-w-[218px]"><span wire:loading wire:target="saveBudget" class="app-spinner"></span>{{ __('ui.save') }}</button><button type="button" x-on:click="open = false" class="btn-soft w-full max-w-[218px]">{{ __('ui.cancel') }}</button></div>
        </form>
    </div>
</x-screen>
