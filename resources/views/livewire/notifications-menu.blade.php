<div class="relative" x-data="{ open: @entangle('open') }" x-on:keydown.escape.window="open = false">
    <button type="button" x-on:click="open = !open" aria-label="Notifikasi" class="relative grid size-[34px] place-items-center rounded-full bg-light text-ink">
        <x-icon name="bell" class="size-4" />
        @if ($unreadCount > 0)
            <span class="absolute -right-1 -top-1 grid min-h-4 min-w-4 place-items-center rounded-full bg-red-500 px-1 text-[9px] font-bold text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak x-on:click.outside="open = false" class="absolute right-0 top-11 z-50 w-[min(340px,calc(100vw-48px))] rounded-2xl bg-white p-4 text-left shadow-xl ring-1 ring-dark/10">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-semibold text-dark">Notifikasi</h2>
            @if ($unreadCount > 0)
                <button type="button" wire:click="markAllRead" class="text-xs font-medium text-blue">Tandai dibaca</button>
            @endif
        </div>
        <div class="max-h-[55vh] space-y-2 overflow-y-auto">
            @forelse ($notifications as $notification)
                <article wire:key="notification-{{ $notification->id }}" class="rounded-xl p-3 {{ $notification->read_at ? 'bg-slate-50' : 'bg-light/70' }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-dark">{{ $notification->data['title'] ?? 'Aktivitas keuangan' }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-dark/75">{{ $notification->data['message'] ?? '' }}</p>
                            <p class="mt-1 text-[10px] text-dark/50">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            @if (! $notification->read_at)
                                <button type="button" wire:click="markRead('{{ $notification->id }}')" aria-label="Tandai dibaca" class="text-xs text-blue">Baca</button>
                            @endif
                            <button type="button" wire:click="delete('{{ $notification->id }}')" aria-label="Hapus notifikasi" class="text-sm text-red-500">&times;</button>
                        </div>
                    </div>
                </article>
            @empty
                <p class="py-6 text-center text-sm text-dark/60">Belum ada notifikasi.</p>
            @endforelse
        </div>
    </div>
</div>
