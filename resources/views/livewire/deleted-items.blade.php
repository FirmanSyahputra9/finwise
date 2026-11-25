<div>
    @if ($items->isNotEmpty())
        <details class="card-3d group mt-6 rounded-2xl bg-white/70 p-4">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                <span>
                    <span class="block text-sm font-semibold text-ink">{{ __('ui.recently_deleted') }}</span>
                    <span class="mt-1 block text-xs text-dark/60">{{ __('ui.undo_period') }}</span>
                </span>
                <span class="text-dark/60 transition-transform group-open:rotate-180">⌄</span>
            </summary>
            <div class="mt-3 flex justify-end">
                <button type="button" wire:click="permanentlyDeleteAll" data-confirm="{{ __('ui.permanent_delete_all_confirm') }}" wire:loading.attr="disabled" wire:target="permanentlyDeleteAll" aria-label="{{ __('ui.permanent_delete_all') }}" title="{{ __('ui.permanent_delete_all') }}" class="grid size-9 place-items-center rounded-full bg-red-100 text-red-700 disabled:opacity-50"><x-icon name="trash-stack" class="size-5" /></button>
            </div>
            <ul class="mt-3 max-h-64 divide-y divide-dark/10 overflow-y-auto overscroll-contain pr-1">
                @foreach ($items as $item)
                    <li wire:key="deleted-{{ $item['type'] }}-{{ $item['id'] }}" class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm text-dark">{{ $item['label'] }}</p>
                            <p class="mt-0.5 text-xs text-dark/60">{{ $item['deletedAt']->diffForHumans() }}</p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <button type="button" wire:click="undo('{{ $item['type'] }}', '{{ $item['id'] }}')" aria-label="{{ __('ui.undo') }}" title="{{ __('ui.undo') }}" class="grid size-9 place-items-center rounded-full bg-main text-ink"><x-icon name="undo" class="size-4" /></button>
                            <button type="button" wire:click="permanentlyDelete('{{ $item['type'] }}', '{{ $item['id'] }}')" data-confirm="{{ __('ui.permanent_delete_confirm') }}" aria-label="{{ __('ui.permanent_delete') }}" title="{{ __('ui.permanent_delete') }}" class="grid size-9 place-items-center rounded-full bg-red-100 text-red-700"><x-icon name="trash" class="size-4" /></button>
                        </div>
                    </li>
                @endforeach
            </ul>
        </details>
    @endif
</div>
