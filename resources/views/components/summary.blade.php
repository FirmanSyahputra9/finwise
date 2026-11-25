@props(['balance', 'expense', 'income'])
@php
    $pct = $income > 0 ? min(100, (int) round($expense / $income * 100)) : 0;
@endphp
<div class="mt-7">
    <div class="grid grid-cols-2 text-center">
        <div class="border-r border-light">
            <p class="summary-balance-label text-xs text-ink">{{ __('ui.total_balance') }}</p>
            <p class="summary-balance-value text-2xl font-bold text-mint">{{ \App\Support\Money::rp($balance) }}</p>
        </div>
        <div>
            <p class="text-xs text-ink">{{ __('ui.expense') }}</p>
            <p class="text-2xl font-semibold text-ocean">-{{ \App\Support\Money::rp($expense) }}</p>
        </div>
    </div>

    <div class="relative mt-5 h-[27px] overflow-hidden rounded-full bg-mint">
        <div class="summary-progress-fill absolute inset-y-0 left-0 flex items-center rounded-full bg-nav px-3 text-xs text-mint"
             style="width: max(14%, {{ $pct }}%)">{{ $pct }}%</div>
        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[13px] font-medium italic text-nav">{{ \App\Support\Money::rp($income) }}</span>
    </div>
    <p class="mt-3 text-center text-[15px] text-nav">{{ __('ui.expense_ratio', ['percent' => $pct, 'advice' => $pct <= 50 ? __('ui.looks_good') : __('ui.be_careful')]) }}</p>
</div>
