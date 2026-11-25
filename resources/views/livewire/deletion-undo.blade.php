<div x-data="{ timer: null, key: null, visible: @js((bool) $type), startTimer() { clearTimeout(this.timer); this.key = String($wire.type || '') + ':' + String($wire.recordId || ''); this.visible = Boolean($wire.type); if (!this.visible) return; const currentKey = this.key; this.timer = setTimeout(() => { if (currentKey === String($wire.type || '') + ':' + String($wire.recordId || '')) { this.visible = false; $wire.call('clear'); } }, 3000); } }" x-init="startTimer()" x-effect="if (String($wire.type || '') + ':' + String($wire.recordId || '') !== key) startTimer()">
    @if ($type)
        <div x-show="visible" x-cloak class="fixed bottom-[100px] left-1/2 z-50 flex w-[min(390px,calc(100vw-32px))] -translate-x-1/2 items-center justify-between gap-3 rounded-2xl bg-nav px-4 py-3 text-mint shadow-xl" role="status">
            <span class="text-sm">{{ __('ui.deleted_temporarily') }}</span>
            <button type="button" wire:click="undo" class="rounded-full bg-main px-4 py-2 text-xs font-semibold text-ink">{{ __('ui.undo') }}</button>
        </div>
    @endif
</div>
