@php($rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.'))
<div x-data>
    <x-screen :title="__('ui.savings_goals')">
        <x-slot:top>
            <x-summary :balance="$balance" :income="$income" :expense="$expense" />
        </x-slot:top>

        <div class="mb-5 flex items-center justify-between">
            <div><h2 class="text-lg font-semibold text-ink">{{ __('ui.dreams') }}</h2><p class="text-xs text-dark/60">{{ __('ui.savings_progress') }}</p></div>
            <button type="button" wire:click="create" class="grid size-11 place-items-center rounded-full bg-dark text-mint" aria-label="{{ __('ui.create_goal') }}"><x-icon name="plus" class="size-6" /></button>
        </div>

        <div class="space-y-4">
            @forelse ($goals as $goal)
                @php($pct = $goal->target_amount > 0 ? min(100, (int) floor($goal->saved_amount / $goal->target_amount * 100)) : 0)
                <article wire:key="goal-{{ $goal->id }}" class="rounded-[22px] bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0"><h3 class="truncate font-semibold text-ink">{{ $goal->name }}</h3>
                            <p class="mt-1 text-xs text-dark/60">{{ $goal->target_date ? __('ui.target').' '.$goal->target_date->translatedFormat('d M Y') : __('ui.no_deadline') }}</p></div>
                        <div class="flex shrink-0 gap-2">
                            <button type="button" wire:click="edit({{ $goal->id }})" class="rounded-full bg-light px-3 py-1 text-xs text-dark">{{ __('ui.edit') }}</button>
                            <button type="button" wire:click="delete({{ $goal->id }})" wire:confirm="{{ __('ui.delete') }} {{ $goal->name }}?" class="rounded-full bg-red-50 px-3 py-1 text-xs text-red-600">{{ __('ui.delete') }}</button>
                        </div>
                    </div>
                    <div class="mt-4 flex items-end justify-between"><p class="text-lg font-bold text-dark">{{ $rp($goal->saved_amount) }}</p><p class="text-xs text-dark/60">{{ __('ui.from_amount') }} {{ $rp($goal->target_amount) }}</p></div>
                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-light"><div class="h-full rounded-full bg-main transition-all" style="width: {{ $pct }}%"></div></div>
                    <div class="mt-1 flex justify-between text-[11px] text-dark/60"><span>{{ $pct }}{{ __('ui.collected') }}</span><span>{{ $goal->saved_amount >= $goal->target_amount ? __('ui.reached') : __('ui.short_by').' '.$rp($goal->target_amount - $goal->saved_amount) }}</span></div>
                    <form wire:submit="addContribution({{ $goal->id }})" class="mt-4 flex gap-2">
                        <x-money-input model="contribution" :value="$contribution" name="contribution" placeholder="{{ __('ui.savings_amount') }}" class="field flex-1" aria-label="{{ __('ui.savings_amount') }}" />
                        <button type="submit" class="rounded-full bg-dark px-4 text-xs font-medium text-mint">{{ __('ui.add') }}</button>
                    </form>
                    @error('contribution') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </article>
            @empty
                <div class="rounded-[22px] bg-white/70 px-5 py-10 text-center"><p class="text-4xl">◎</p><p class="mt-3 font-semibold text-ink">{{ __('ui.no_goals') }}</p><p class="mt-1 text-sm text-dark/60">{{ __('ui.dream_start') }}</p><button type="button" wire:click="create" class="btn mt-5">{{ __('ui.first_goal') }}</button></div>
            @endforelse
        </div>
    </x-screen>

    @if ($formOpen)
        <div class="fixed inset-0 z-40 grid place-items-center bg-black/50 px-6" wire:click.self="$set('formOpen', false)" x-on:keydown.escape.window="$wire.set('formOpen', false)">
            <form wire:submit="save" class="w-full max-w-[360px] rounded-[24px] bg-white p-6">
                <h2 class="text-center text-xl font-bold text-dark">{{ $editingId ? __('ui.edit_goal') : __('ui.new_goal') }}</h2>
                <label class="label" for="goal-name">{{ __('ui.goal_name') }}</label><input id="goal-name" type="text" wire:model="name" placeholder="{{ __('ui.goal_name') }}" class="field">
                @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                <label class="label" for="goal-amount">{{ __('ui.goal_amount') }}</label><x-money-input id="goal-amount" model="targetAmount" :value="$targetAmount" name="target_amount" placeholder="5,000,000" class="field" />
                @error('targetAmount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                <label class="label" for="goal-date">{{ __('ui.deadline') }} <span class="font-normal text-dark/50">{{ __('ui.optional') }}</span></label><input id="goal-date" type="date" wire:model="targetDate" class="field">
                @error('targetDate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                <button type="submit" class="btn mt-6 w-full">{{ __('ui.save_goal') }}</button>
                <button type="button" wire:click="$set('formOpen', false)" class="btn-soft mt-2 w-full">{{ __('ui.cancel') }}</button>
            </form>
        </div>
    @endif
</div>
