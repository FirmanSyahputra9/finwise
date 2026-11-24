@props(['t', 'odd' => true])
<div class="flex items-center gap-4 border-b border-light py-3">
    <div class="grid h-[53px] w-14 shrink-0 place-items-center rounded-[22px] text-xl font-semibold text-mint {{ $odd ? 'bg-sky' : 'bg-blue' }}">
        {{ mb_strtoupper(mb_substr($t->category?->name ?? 'T', 0, 1)) }}
    </div>
    <div class="min-w-0 flex-1">
        <p class="truncate text-[15px] font-medium text-nav">{{ $t->category?->name ?? 'Transfer' }}</p>
        <p class="text-xs font-semibold text-ocean">{{ $t->date->translatedFormat('d M Y') }} · {{ $t->account->name }}</p>
    </div>
    <div class="text-right">
        <p class="text-[15px] font-medium {{ $t->type->value === 'income' ? 'text-ink' : 'text-ocean' }}">
            {{ $t->type->value === 'expense' ? '-' : '' }}{{ \App\Support\Money::rp($t->amount) }}
        </p>
        @if ($t->note)
            <p class="ml-auto max-w-24 truncate text-[13px] font-light text-nav">{{ $t->note }}</p>
        @endif
    </div>
</div>
