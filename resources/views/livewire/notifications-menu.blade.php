<div class="relative flex items-center gap-2" x-data="{ open: @entangle('open'), toastVisible: false, toastMessage: '', toastType: 'success', toastTimer: null, showToast(detail) { this.toastMessage = detail.message || ''; this.toastType = detail.type || 'success'; this.toastVisible = Boolean(this.toastMessage); clearTimeout(this.toastTimer); if (detail.duration !== 0) this.toastTimer = setTimeout(() => this.toastVisible = false, detail.duration || 3000); }, closeToast() { this.toastVisible = false; clearTimeout(this.toastTimer); } }" x-on:keydown.escape.window="open = false" x-on:app-toast.window="showToast($event.detail)">
    <div x-show="toastVisible" x-transition:enter="origin-right transition-all duration-300 ease-out" x-transition:enter-start="scale-[.08] opacity-0" x-transition:enter-end="scale-100 opacity-100" x-transition:leave="origin-right transition-all duration-250 ease-in pointer-events-none" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-[.08] opacity-0" x-cloak
         class="app-toast-position flex max-w-[min(310px,calc(100vw-92px))] items-center gap-2 rounded-2xl px-3 py-2.5 text-xs font-medium shadow-xl ring-1 ring-black/10"
         :class="toastType === 'error' ? 'bg-red-600 text-white' : 'bg-nav text-mint'" role="status" aria-live="polite">
        <span x-show="toastType === 'loading'" class="app-spinner size-4 shrink-0 border-mint/40 border-t-mint"></span>
        <span class="min-w-0 flex-1" x-text="toastMessage"></span>
        <button type="button" x-on:click="closeToast()" class="shrink-0 text-base leading-none opacity-70" aria-label="{{ __('ui.close') }}">&times;</button>
    </div>
    <button type="button" x-on:click="open = !open" aria-label="{{ __('ui.notifications') }}" data-notification-trigger class="relative grid size-[34px] place-items-center rounded-full bg-light text-ink">
        <x-icon name="bell" class="size-4" />
        @if ($unreadCount > 0)
            <span class="absolute -right-1 -top-1 grid min-h-4 min-w-4 place-items-center rounded-full bg-red-500 px-1 text-[9px] font-bold text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak x-on:click.outside="open = false" class="absolute right-0 top-11 z-50 w-[min(340px,calc(100vw-48px))] rounded-2xl bg-white p-4 text-left shadow-xl ring-1 ring-dark/10">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-semibold text-dark">{{ __('ui.notifications') }}</h2>
            <div class="flex gap-2">
                @if ($unreadCount > 0)
                    <button type="button" wire:click="markAllRead" aria-label="{{ __('ui.mark_read') }}" title="{{ __('ui.mark_read') }}" class="grid size-8 place-items-center rounded-full bg-light text-blue"><x-icon name="check-all" class="size-4" /></button>
                @endif
                @if ($notifications->isNotEmpty())
                    <button type="button" wire:click="deleteAll" wire:confirm="{{ __('ui.delete_all_notifications_confirm') }}" aria-label="{{ __('ui.delete_all_notifications') }}" title="{{ __('ui.delete_all_notifications') }}" class="grid size-8 place-items-center rounded-full bg-red-100 text-red-700"><x-icon name="trash-stack" class="size-4" /></button>
                @endif
            </div>
        </div>
        <div class="max-h-[55vh] space-y-2 overflow-y-auto">
            @forelse ($notifications as $notification)
                <article wire:key="notification-{{ $notification->id }}" class="rounded-xl p-3 {{ $notification->read_at ? 'bg-slate-50' : 'bg-light/70' }}">
                    @php($messageParams = $notification->data['message_params'] ?? [])
                    @if (isset($messageParams['type'])) @php($messageParams['type'] = __('ui.'.$messageParams['type'])) @endif
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-dark">{{ isset($notification->data['title_key']) ? __('ui.'.$notification->data['title_key']) : ($notification->data['title'] ?? __('ui.financial_activity')) }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-dark/75">{{ isset($notification->data['message_key']) ? __('ui.'.$notification->data['message_key'], $messageParams) : ($notification->data['message'] ?? '') }}</p>
                            <p class="mt-1 text-[10px] text-dark/50">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            @if (! $notification->read_at)
                                <button type="button" wire:click="markRead('{{ $notification->id }}')" aria-label="{{ __('ui.mark_read') }}" title="{{ __('ui.mark_read') }}" class="grid size-8 place-items-center rounded-full bg-sky/10 text-blue"><x-icon name="check" class="size-4" /></button>
                            @endif
                            <button type="button" wire:click="delete('{{ $notification->id }}')" wire:loading.attr="disabled" wire:target="delete" aria-label="{{ __('ui.delete_notification') }}" class="text-sm text-red-500 disabled:opacity-50">&times;</button>
                        </div>
                    </div>
                </article>
            @empty
                <p class="py-6 text-center text-sm text-dark/60">{{ __('ui.no_notifications') }}</p>
            @endforelse
        </div>
    </div>
</div>
