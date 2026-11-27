@props(['balance', 'expense', 'income'])
@php
    $pct = $income > 0 ? min(100, (int) round($expense / $income * 100)) : ($expense > 0 ? 100 : 0);
    $progressHue = 120 - ((int) round($pct / 100 * 24) * 5);
    $expenseStatus = match (true) {
        $expense > $income => __('ui.expense_status_exceeded'),
        $income > 0 && $expense === $income => __('ui.expense_status_limit'),
        $pct <= 20 => __('ui.expense_status_safe'),
        $pct <= 40 => __('ui.expense_status_controlled'),
        $pct <= 60 => __('ui.expense_status_attention'),
        default => __('ui.expense_status_high'),
    };
@endphp
<div data-summary-card class="card-3d mx-5 mt-7 rounded-[26px] bg-white p-5 text-dark shadow-lg">
    <div class="grid grid-cols-2 text-center">
        <div class="border-r border-dark/10">
            <p class="summary-balance-label text-xs text-nav/65">{{ __('ui.total_balance') }}</p>
            <p data-fit-amount class="summary-balance-value text-2xl font-bold text-nav">{{ \App\Support\Money::rp($balance) }}</p>
        </div>
        <div>
            <p class="text-xs text-nav/60">{{ __('ui.expense') }}</p>
            <p data-fit-amount class="text-2xl font-semibold text-rose-600">{{ \App\Support\Money::rp($expense) }}</p>
        </div>
    </div>

    <div class="relative mt-5 h-[27px] overflow-hidden rounded-full bg-mint">
        <div class="summary-progress-fill absolute inset-y-0 left-0 flex items-center rounded-full bg-nav px-3 text-xs text-mint"
             style="--summary-progress-hue: {{ $progressHue }}; width: max(14%, {{ $pct }}%); background-color: hsl(var(--summary-progress-hue) 72% 30%)">{{ $pct }}%</div>
        <span data-fit-amount class="absolute right-3 top-1/2 inline-block max-w-[calc(100%-5rem)] -translate-y-1/2 text-right text-[13px] font-medium italic text-nav">{{ \App\Support\Money::rp($income) }}</span>
    </div>
    <p class="summary-expense-ratio mt-3 text-center text-[15px]" style="--summary-progress-hue: {{ $progressHue }}; color: hsl(var(--summary-progress-hue) 72% 30%)">{{ __('ui.expense_ratio', ['percent' => $pct, 'advice' => $expenseStatus]) }}</p>
</div>
