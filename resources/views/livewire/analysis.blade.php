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

        <div x-data="{ categoryFilterOpen: false }" class="group relative mt-4">
            <button type="button" x-on:click="categoryFilterOpen = !categoryFilterOpen" x-bind:aria-expanded="categoryFilterOpen" aria-controls="analysis-category-filter-options" class="card-3d flex w-full cursor-pointer list-none items-center justify-between rounded-2xl bg-white/70 px-4 py-3 text-left text-sm font-medium text-ink">
                @php($selectedCategoryCount = count($analysisIncomeCategoryIds) + count($analysisExpenseCategoryIds))
                <span>{{ __('ui.category_filter') }} <span class="font-normal text-dark/60">({{ $selectedCategoryCount ? $selectedCategoryCount.' '.__('ui.selected') : __('ui.all_categories') }})</span></span>
                <span x-bind:class="categoryFilterOpen ? 'rotate-180' : ''" class="text-dark/60 transition">&#8964;</span>
            </button>
            <div x-show="categoryFilterOpen" x-cloak id="analysis-category-filter-options" class="card-3d mt-2 max-h-80 overflow-y-auto rounded-2xl bg-white p-2">
                <div role="group" aria-label="{{ __('ui.category_type') }}" class="segmented-control grid grid-cols-2 gap-1 rounded-full bg-light p-1">
                    <button type="button" aria-pressed="{{ $analysisCategoryType === 'income' ? 'true' : 'false' }}" wire:click="setAnalysisCategoryType('income')" class="rounded-full px-3 py-2 text-sm {{ $analysisCategoryType === 'income' ? 'bg-main font-medium text-ink' : 'text-dark/70' }}">{{ __('ui.in') }}</button>
                    <button type="button" aria-pressed="{{ $analysisCategoryType === 'expense' ? 'true' : 'false' }}" wire:click="setAnalysisCategoryType('expense')" class="rounded-full px-3 py-2 text-sm {{ $analysisCategoryType === 'expense' ? 'bg-main font-medium text-ink' : 'text-dark/70' }}">{{ __('ui.out') }}</button>
                </div>
                <section aria-label="{{ $analysisCategoryType === 'income' ? __('ui.in') : __('ui.out') }}" class="pt-2">
                    @if ($analysisCategoryType === 'income')
                        @forelse ($analysisIncomeCategories as $category)
                            @php($active = in_array((int) $category->id, array_map('intval', $analysisIncomeCategoryIds), true))
                            <button type="button" wire:key="analysis-income-category-{{ $category->id }}" wire:click="toggleAnalysisCategory({{ $category->id }})" aria-pressed="{{ $active ? 'true' : 'false' }}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm hover:bg-light">
                                <span class="grid size-5 place-items-center rounded border {{ $active ? 'border-dark bg-dark text-white' : 'border-dark/30' }}">@if ($active)<x-icon name="check" class="size-3" />@endif</span>
                                <span class="min-w-0 flex-1 truncate text-ink">{{ $category->name }}</span>
                            </button>
                        @empty
                            <p class="px-3 py-2 text-sm text-dark/60">{{ __('ui.no_categories') }}</p>
                        @endforelse
                        @if ($analysisIncomeCategoryIds)
                            <button type="button" wire:click="clearAnalysisCategories('income')" class="mt-1 w-full border-t border-dark/10 px-3 py-2 text-left text-xs font-medium text-blue">{{ __('ui.clear_selection') }} ({{ __('ui.in') }})</button>
                        @endif
                    @else
                        @forelse ($analysisExpenseCategories as $category)
                            @php($active = in_array((int) $category->id, array_map('intval', $analysisExpenseCategoryIds), true))
                            <button type="button" wire:key="analysis-expense-category-{{ $category->id }}" wire:click="toggleAnalysisCategory({{ $category->id }})" aria-pressed="{{ $active ? 'true' : 'false' }}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm hover:bg-light">
                                <span class="grid size-5 place-items-center rounded border {{ $active ? 'border-dark bg-dark text-white' : 'border-dark/30' }}">@if ($active)<x-icon name="check" class="size-3" />@endif</span>
                                <span class="min-w-0 flex-1 truncate text-ink">{{ $category->name }}</span>
                            </button>
                        @empty
                            <p class="px-3 py-2 text-sm text-dark/60">{{ __('ui.no_categories') }}</p>
                        @endforelse
                        @if ($analysisExpenseCategoryIds)
                            <button type="button" wire:click="clearAnalysisCategories('expense')" class="mt-1 w-full border-t border-dark/10 px-3 py-2 text-left text-xs font-medium text-blue">{{ __('ui.clear_selection') }} ({{ __('ui.out') }})</button>
                        @endif
                    @endif
                </section>
            </div>
        </div>
        @if ($analysisIncomeCategoryIds || $analysisExpenseCategoryIds)
            <div class="card-3d mt-3 space-y-1 rounded-2xl bg-white/70 px-4 py-3 text-xs leading-relaxed text-dark/70">
                @if ($analysisIncomeCategoryIds)
                    <p>{{ __('ui.in') }}: {{ implode(', ', $selectedAnalysisIncomeCategories) }}</p>
                @endif
                @if ($analysisExpenseCategoryIds)
                    <p>{{ __('ui.out') }}: {{ implode(', ', $selectedAnalysisExpenseCategories) }}</p>
                @endif
            </div>
        @endif
        <div wire:key="analysis-chart-{{ $chartType }}" x-data="{ savedChart: window.finwiseAppearance.read().analysisChart }" x-init="if (savedChart !== @js($chartType)) $wire.set('chartType', savedChart)" class="card-3d mt-5 rounded-[36px] bg-light px-6 py-6">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-[15px] font-medium text-ink">
                    @if ($chartType === 'pie')
                        {{ __('ui.category_distribution') }} <span class="font-normal text-dark/60">({{ $analysisCategoryType === 'income' ? __('ui.in') : __('ui.out') }})</span>
                    @else
                        {{ __('ui.income_expense') }}
                    @endif
                </p>
                @unless ($chartType === 'pie')
                    <div role="group" aria-label="{{ __('ui.average_basis') }}" class="segmented-control flex gap-1 rounded-full bg-white/70 p-1">
                        @foreach (['all' => __('ui.average_all_periods'), 'active' => __('ui.average_active_periods')] as $value => $label)
                            <button type="button" aria-pressed="{{ $averageMode === $value ? 'true' : 'false' }}" wire:click="$set('averageMode', '{{ $value }}')" class="rounded-full px-3 py-1.5 text-[10px] font-medium {{ $averageMode === $value ? 'bg-main text-ink' : 'text-dark/70' }}">{{ $label }}</button>
                        @endforeach
                    </div>
                @endunless
            </div>
            @if ($chartType === 'bar' || $chartType === 'line')
                <div class="mt-3 flex justify-center gap-4 text-[11px] text-dark/70">
                    <span><span class="mr-1 inline-block size-2 rounded-full bg-main"></span>{{ __('ui.in') }}</span>
                    <span><span class="mr-1 inline-block size-2 rounded-full bg-ocean"></span>{{ __('ui.out') }}</span>
                </div>
                @if ($chartType === 'bar')
                    <div class="mt-3 flex h-[132px] items-end justify-around gap-2 border-b border-dark px-2">
                        @foreach ($rows as $r)
                            <div class="flex h-full min-w-0 flex-1 items-end justify-center gap-1">
                                <span title="{{ $r['label'] }} - {{ __('ui.in') }} IDR {{ number_format($r['income'], 0, ',', '.') }}" aria-label="{{ $r['label'] }} - {{ __('ui.in') }} IDR {{ number_format($r['income'], 0, ',', '.') }}" class="w-2 flex-1 max-w-4 rounded-t-full bg-main" style="height: {{ $r['income'] ? max(4, (int) round($r['income'] / $max * 132)) : 0 }}px"></span>
                                <span title="{{ $r['label'] }} - {{ __('ui.out') }} IDR {{ number_format($r['expense'], 0, ',', '.') }}" aria-label="{{ $r['label'] }} - {{ __('ui.out') }} IDR {{ number_format($r['expense'], 0, ',', '.') }}" class="w-2 flex-1 max-w-4 rounded-t-full bg-ocean" style="height: {{ $r['expense'] ? max(4, (int) round($r['expense'] / $max * 132)) : 0 }}px"></span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <svg viewBox="0 0 280 132" role="img" aria-label="{{ __('ui.income_expense') }}" class="mt-3 h-[132px] w-full overflow-visible border-b border-dark">
                        <path d="M8 10H272 M8 68H272 M8 126H272" stroke="currentColor" stroke-opacity=".1" />
                        <polyline points="{{ $incomeLinePoints }}" fill="none" stroke="var(--color-main)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                        <polyline points="{{ $expenseLinePoints }}" fill="none" stroke="var(--color-ocean)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                        @foreach ($rows as $index => $r)
                            @php($x = $chartCoordinates[$index]['x'])
                            <circle cx="{{ $x }}" cy="{{ $chartCoordinates[$index]['incomeY'] }}" r="3.5" fill="var(--color-main)"><title>{{ $r['label'] }} - {{ __('ui.in') }} IDR {{ number_format($r['income'], 0, ',', '.') }}</title></circle>
                            <circle cx="{{ $x }}" cy="{{ $chartCoordinates[$index]['expenseY'] }}" r="3.5" fill="var(--color-ocean)"><title>{{ $r['label'] }} - {{ __('ui.out') }} IDR {{ number_format($r['expense'], 0, ',', '.') }}</title></circle>
                        @endforeach
                    </svg>
                @endif
                <div class="flex justify-between px-1 pt-1.5 font-spartan text-sm text-dark">
                    @foreach ($rows as $r) <span>{{ $r['label'] }}</span> @endforeach
                </div>
            @else
                @if ($pieTotal > 0)
                    <div class="mt-4 flex h-[132px] items-center gap-4">
                        <svg viewBox="0 0 100 100" role="img" aria-label="{{ __('ui.category_distribution') }} - {{ $analysisCategoryType === 'income' ? __('ui.in') : __('ui.out') }}" class="size-[112px] shrink-0">
                            <circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-opacity=".12" stroke-width="18" />
                            @foreach ($pieCategories as $slice)
                                <circle cx="50" cy="50" r="40" fill="none" stroke="{{ $slice['color'] }}" stroke-width="18" stroke-dasharray="{{ number_format($slice['length'], 3, '.', '') }} {{ number_format($pieCircumference, 3, '.', '') }}" stroke-dashoffset="-{{ number_format($slice['offset'], 3, '.', '') }}" transform="rotate(-90 50 50)"><title>{{ $slice['name'] }} - IDR {{ number_format($slice['amount'], 0, ',', '.') }}</title></circle>
                            @endforeach
                            <text x="50" y="53" text-anchor="middle" class="fill-ink" font-size="8" font-weight="600">{{ number_format($pieTotal, 0, ',', '.') }}</text>
                        </svg>
                        <div class="max-h-[132px] min-w-0 flex-1 space-y-1 overflow-y-auto">
                            @foreach ($pieCategories as $slice)
                                <div title="{{ $slice['name'] }}" class="flex items-center gap-2 text-[10px] text-dark">
                                    <span class="size-2 shrink-0 rounded-full" style="background: {{ $slice['color'] }}"></span>
                                    <span class="min-w-0 flex-1 truncate">{{ $slice['name'] }}</span>
                                    <span class="shrink-0">{{ \App\Support\Money::rp($slice['amount']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <p class="py-10 text-center text-sm text-dark/60">{{ __('ui.no_transactions') }}</p>
                @endif
            @endif
            @unless ($chartType === 'pie')
                <div class="mt-5 grid grid-cols-2 text-center">
                    <div>
                        <p class="text-[12px] font-medium text-ink"><span class="mr-1 inline-block size-2 rounded-full bg-main"></span>{{ __('ui.average_income_period') }}</p>
                        <p data-fit-amount class="text-lg font-semibold text-ink">{{ \App\Support\Money::rp($averageIncome) }}</p>
                    </div>
                    <div>
                        <p class="text-[12px] font-medium text-ink"><span class="mr-1 inline-block size-2 rounded-full bg-ocean"></span>{{ __('ui.average_expense_period') }}</p>
                        <p data-fit-amount class="text-lg font-semibold text-ocean">{{ \App\Support\Money::rp($averageExpense) }}</p>
                    </div>
                </div>
            @endunless
        </div>
        <div class="mb-3 mt-6 flex items-center justify-between">
            <h2 class="text-[15px] font-medium text-ink">{{ __('ui.monthly_budget') }}</h2>
            <button type="button" wire:click="createBudget" aria-label="{{ __('ui.add_budget') }}" title="{{ __('ui.add_budget') }}" class="dark-contrast-action grid size-9 place-items-center rounded-full bg-main text-ink"><x-icon name="plus" class="size-4" /></button>
        </div>
        <form wire:submit="saveMonthlyBudget" class="card-3d mb-4 rounded-2xl bg-light p-4">
            <label for="monthly-budget" class="label mt-0">{{ __('ui.monthly_budget_limit') }}</label>
            <div class="flex gap-2"><x-money-input id="monthly-budget" model="monthlyBudget" :value="$monthlyBudget" name="monthly_budget" placeholder="1,500,000" class="field" /><button class="btn inline-flex shrink-0 items-center gap-2 px-5"><span wire:loading wire:target="saveMonthlyBudget" class="app-spinner"></span>{{ __('ui.save') }}</button></div>
            @error('monthlyBudget')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            @if ($monthlyBudget)
                <p class="mt-3 flex min-w-0 items-center gap-2 text-[10px] leading-tight text-dark/70">
                    <span class="min-w-0 flex-1 truncate">{{ __('ui.remaining_month') }}</span>
                    <span data-fit-amount class="shrink-0 whitespace-nowrap text-right">{{ \App\Support\Money::rp($monthlyBudget - $expense) }}</span>
                </p>
            @endif
        </form>
        <div class="grid grid-cols-2 gap-4">
            @if ($budgetCards->isEmpty())
                <p class="col-span-2 text-center text-sm text-nav/60">{{ $monthlyBudget ? __('ui.no_category_budget') : __('ui.no_budget') }}</p>
            @endif
            @foreach ($budgetCards as $card)
                <div wire:key="budget-{{ $card['id'] }}" class="card-3d relative flex h-[190px] flex-col items-center justify-center gap-2 rounded-[36px] bg-sky px-2">
                    <div class="absolute right-3 top-3 flex gap-2 text-xs font-medium text-white">
                        <button type="button" wire:click="editBudget({{ $card['id'] }})" aria-label="{{ __('ui.edit_budget') }}" title="{{ __('ui.edit_budget') }}" class="grid size-6 place-items-center rounded-full bg-white/15"><x-icon name="edit" class="size-2.5" /></button>
                        <button type="button" wire:click="deleteBudget({{ $card['id'] }})" wire:loading.attr="disabled" wire:target="deleteBudget" data-confirm="{{ __('ui.delete_category_budget') }}" aria-label="{{ __('ui.delete') }}" title="{{ __('ui.delete') }}" class="grid size-6 place-items-center rounded-full bg-white/15 disabled:opacity-50"><x-icon name="trash" class="size-2.5" /></button>
                    </div>
                    <div class="grid size-[96px] place-items-center rounded-full" style="background: conic-gradient(var(--color-ocean) {{ $card['percentage'] }}%, #F1FFF3 0)">
                        <div class="analysis-budget-percent grid size-[74px] place-items-center rounded-full bg-sky text-xl font-semibold text-mint">{{ $card['percentage'] }}%</div>
                    </div>
                    <p class="truncate text-[15px] font-medium text-mint">{{ $card['name'] }}</p>
                    <p data-fit-amount class="max-w-full text-center text-[10px] text-mint/90">{{ \App\Support\Money::rp($card['spent']) }} / {{ \App\Support\Money::rp($card['amount']) }}</p>
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
