<div wire:poll.5s class="h-dvh overflow-hidden bg-white">
    @php($person = $connection?->otherUser(auth()->id()))
    @php($isGroupAdmin = $group?->members->firstWhere('id', auth()->id())?->pivot->role === 'admin')
    <section class="mx-auto flex h-full min-h-0 w-full max-w-[430px] flex-col overflow-hidden bg-white" role="region" aria-label="{{ $group?->name ?? $person?->name }}">
        <header class="flex shrink-0 items-center gap-3 border-b border-dark/10 bg-white px-4 py-3">
            <a href="{{ route('chat') }}" wire:navigate aria-label="{{ __('ui.close') }}" class="grid size-10 shrink-0 place-items-center rounded-full bg-light text-dark"><x-icon name="back" class="size-5" /></a>
            @if ($group)
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-dark font-semibold text-mint">{{ mb_strtoupper(mb_substr($group->name, 0, 1)) }}</span>
            @else
                <span class="grid size-11 shrink-0 place-items-center overflow-hidden rounded-full bg-sky font-semibold text-white">@if ($person?->profile_photo_path)<img src="{{ Storage::disk('public')->url($person->profile_photo_path) }}" alt="" class="h-full w-full object-cover">@else{{ mb_strtoupper(mb_substr($person?->name ?? '?', 0, 1)) }}@endif</span>
            @endif
            <div class="min-w-0 flex-1"><h2 class="truncate font-semibold text-dark">{{ $group?->name ?? $person?->name }}</h2>
                <p class="text-[11px] text-dark/50">{{ $group ? $group->members->count().' '.__('ui.chat_group_members_short') : __('ui.chat_text_only') }}</p></div>
            @if ($group && $isGroupAdmin)
                <button type="button" wire:click="deleteGroup({{ $group->id }})" data-confirm="{{ __('ui.chat_group_delete_confirm') }}" aria-label="{{ __('ui.chat_group_delete') }}" class="grid size-9 shrink-0 place-items-center rounded-full bg-red-100 text-red-700"><x-icon name="trash" class="size-4" /></button>
            @elseif ($connection)
                <button type="button" wire:click="disconnect({{ $connection->id }})" data-confirm="{{ __('ui.chat_disconnect_confirm') }}" class="shrink-0 rounded-full bg-red-100 px-3 py-2 text-xs font-medium text-red-700">{{ __('ui.chat_disconnect') }}</button>
            @endif
        </header>

        @if ($group && $isGroupAdmin)
            <details class="max-h-40 shrink-0 overflow-y-auto border-b border-dark/10 bg-white px-4 py-2">
                <summary class="cursor-pointer text-xs font-medium text-nav">{{ __('ui.chat_group_manage') }}</summary>
                @error('groupMembers')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                <form wire:submit="inviteToGroup({{ $group->id }})" class="mt-2 flex gap-2">
                    <select wire:model="inviteUserId" class="field min-w-0 flex-1"><option value="">{{ __('ui.chat_group_choose_invite') }}</option>@foreach ($inviteableUsers as $inviteablePerson)<option value="{{ $inviteablePerson->id }}">{{ $inviteablePerson->name }}</option>@endforeach</select>
                    <button class="btn shrink-0 px-3">{{ __('ui.chat_group_invite') }}</button>
                </form>
                @error('inviteUserId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                <ul class="mt-2 max-h-28 space-y-1 overflow-y-auto">
                    @foreach ($group->members as $member)
                        <li wire:key="group-member-{{ $group->id }}-{{ $member->id }}" class="flex items-center justify-between gap-2 text-xs"><span class="truncate">{{ $member->name }} @if ($member->pivot->role === 'admin') · {{ __('ui.chat_group_admin') }} @endif</span>@if ($member->id !== auth()->id() || $group->members->where('pivot.role', 'admin')->count() > 1)<button type="button" wire:click="removeGroupMember({{ $group->id }}, {{ $member->id }})" data-confirm="{{ __('ui.chat_group_remove_confirm') }}" class="text-red-600">{{ __('ui.chat_group_remove') }}</button>@endif</li>
                    @endforeach
                </ul>
            </details>
        @endif

        @if ($connection || ($group && $isGroupAdmin))
            <details class="shrink-0 border-b border-dark/10 px-4 py-2">
                <summary class="cursor-pointer text-xs font-medium text-nav">{{ __('ui.chat_retention') }} · {{ __('ui.chat_retention_days', ['days' => $connection?->retention_days ?? $group?->retention_days ?? 30]) }}</summary>
                <div x-data="{ open: false }" x-on:keydown.escape.window="open = false" x-on:click.outside="open = false" class="relative mt-2">
                    <span class="text-xs text-dark/70">{{ __('ui.chat_retention_label') }}</span>
                    @php($currentRetentionDays = $connection?->retention_days ?? $group?->retention_days ?? 30)
                    <button type="button" x-on:click="open = !open" aria-haspopup="listbox" x-bind:aria-expanded="open.toString()" class="field mt-1 flex h-8 min-h-0 w-full max-w-36 items-center justify-between px-3 py-1 text-left text-xs"><span>{{ __('ui.chat_retention_days', ['days' => $currentRetentionDays]) }}</span><x-icon name="chevron-down" class="size-3.5" /></button>
                    <div x-cloak x-show="open" role="listbox" class="absolute inset-x-0 top-full z-30 mt-1 max-h-[7.5rem] overflow-y-auto overscroll-contain rounded-xl border border-dark/10 bg-white p-1 shadow-lg">
                        @foreach ([1, 3, 5, 7, 15, 30] as $days)
                            <button type="button" role="option" aria-selected="{{ $currentRetentionDays === $days ? 'true' : 'false' }}" wire:click="updateRetentionDays({{ $days }})" x-on:click="open = false" class="block w-full rounded-lg px-3 py-1.5 text-left text-xs hover:bg-light {{ $currentRetentionDays === $days ? 'bg-light font-semibold text-nav' : 'text-dark' }}">{{ __('ui.chat_retention_days', ['days' => $days]) }}</button>
                        @endforeach
                    </div>
                </div>
            </details>
        @endif

        <div id="chat-messages" data-chat-user-id="{{ auth()->id() }}" class="min-h-0 flex-1 space-y-2 overflow-y-auto px-4 py-3">
            @if ($hasOlderMessages)
                <div class="py-1 text-center"><button type="button" wire:click="loadOlderMessages" class="rounded-full bg-light px-3 py-1.5 text-xs text-dark/70">{{ __('ui.chat_load_older') }}</button></div>
            @endif
            @forelse ($messages as $chatMessage)
                @if ($chatMessage->id === $unreadMessageId)
                    <div data-chat-first-unread class="py-2 text-center"><span class="rounded-full bg-sky/10 px-3 py-1 text-[10px] font-medium text-nav">{{ __('ui.chat_unread_messages') }}</span></div>
                @endif
                <div id="chat-message-{{ $chatMessage->id }}" data-chat-message-id="{{ $chatMessage->id }}" data-chat-sender-id="{{ $chatMessage->sender_id }}" wire:key="chat-message-{{ $chatMessage->id }}" class="flex {{ $chatMessage->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                    <div class="flex max-w-[90%] items-start gap-2 {{ $chatMessage->sender_id === auth()->id() ? 'flex-row-reverse' : '' }}">
                        @if ($group)
                            <span class="grid size-8 shrink-0 place-items-center overflow-hidden rounded-full bg-sky text-[10px] font-semibold text-white">@if ($chatMessage->sender?->profile_photo_path)<img src="{{ Storage::disk('public')->url($chatMessage->sender->profile_photo_path) }}" alt="" class="h-full w-full object-cover">@else{{ mb_strtoupper(mb_substr($chatMessage->sender?->name ?? '?', 0, 1)) }}@endif</span>
                        @endif
                        <div class="min-w-0">
                            @if ($group)<p class="mb-1 truncate text-[10px] font-medium text-dark/60 {{ $chatMessage->sender_id === auth()->id() ? 'text-right' : '' }}">{{ $chatMessage->sender?->name }}</p>@endif
                            @php($attachments = $chatMessage->imageAttachments())
                            <div class="rounded-2xl px-2 py-2 text-sm {{ $chatMessage->sender_id === auth()->id() ? ($group ? 'rounded-tr-md' : 'rounded-br-md').' bg-main text-dark' : ($group ? 'rounded-tl-md' : 'rounded-bl-md').' bg-light text-dark' }}">
                                @if ($attachments !== [])
                                    @php($gallery = collect($attachments)->map(function ($image, $index) use ($chatMessage) { $uuid = App\Support\ChatConversationUuid::from('attachment', $chatMessage->id, $index); $url = route('chat.attachment', $uuid); return ['url' => $url, 'downloadUrl' => $url.'?download=1', 'downloadName' => 'putraganteng-'.$uuid.'.webp']; })->values()->all())
                                    <div class="{{ count($attachments) > 1 ? 'grid w-full max-w-56 grid-cols-2 gap-1' : 'w-fit' }}">
                                        @foreach (range(0, min(count($attachments), 4) - 1) as $index)
                                            @if (count($attachments) > 4 && $index === 3)
                                                <button type="button" data-chat-image-open data-image-gallery="{{ json_encode($gallery, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}" data-image-index="3" aria-label="{{ __('ui.chat_photo_open') }}" class="relative aspect-square w-full overflow-hidden rounded-xl bg-black/70 text-2xl font-semibold text-white">+{{ count($attachments) - 3 }}</button>
                                            @else
                                                <button type="button" data-chat-image-open data-image-gallery="{{ json_encode($gallery, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}" data-image-index="{{ $index }}" aria-label="{{ __('ui.chat_photo_open') }}" class="{{ count($attachments) > 1 ? 'relative aspect-square w-full overflow-hidden rounded-xl' : 'block overflow-hidden rounded-xl' }}">
                                                    <img src="{{ $gallery[$index]['url'] }}" alt="{{ __('ui.chat_image') }}" loading="lazy" class="{{ count($attachments) > 1 ? 'size-full object-cover' : 'max-h-40 max-w-48 object-contain' }}">
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                    @if ($attachments[0]['caption'] !== '')<p class="whitespace-pre-wrap break-words px-1 pt-2">{{ $attachments[0]['caption'] }}</p>@endif
                                @else
                                    <p class="whitespace-pre-wrap break-words px-1">{{ $chatMessage->body }}</p>
                                @endif
                                <time wire:ignore data-chat-local-time datetime="{{ $chatMessage->created_at->copy()->utc()->toIso8601String() }}" class="mt-1 block text-right text-[10px] opacity-60">{{ $chatMessage->created_at->format('H:i') }}</time>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-dark/50">{{ __('ui.chat_first_message') }}</p>
            @endforelse
            @if ($hasNewerMessages)
                <div class="py-1 text-center"><button type="button" wire:click="loadNewerMessages" class="rounded-full bg-light px-3 py-1.5 text-xs text-dark/70">{{ __('ui.chat_load_newer') }}</button></div>
            @endif
        </div>

        <form wire:submit="send" data-chat-composer class="shrink-0 border-t border-dark/10 bg-white p-3">
            <div class="flex items-end gap-2">
                <label class="btn-soft grid size-11 shrink-0 cursor-pointer place-items-center px-0" aria-label="{{ __('ui.chat_add_photo') }}" title="{{ __('ui.chat_add_photo') }}"><x-icon name="image" class="size-5" /><input type="file" accept="image/jpeg,image/png,image/webp" multiple data-chat-photo-input data-chat-photo-converting="{{ __('ui.chat_photo_converting') }}" data-chat-photo-uploading="{{ __('ui.chat_photo_uploading') }}" data-chat-photo-ready="{{ __('ui.chat_photo_ready') }}" data-chat-photo-error="{{ __('ui.chat_photo_conversion_error') }}" data-chat-photo-failed="{{ __('ui.chat_photo_failed') }}" data-chat-photo-limit-error="{{ __('ui.chat_photo_too_many') }}" data-chat-photo-busy="{{ __('ui.chat_photo_busy') }}" class="sr-only"></label>
                <textarea wire:model="message" rows="1" maxlength="2000" class="field max-h-28 min-h-11 resize-y flex-1" placeholder="{{ __('ui.chat_message_placeholder') }}"></textarea>
                <button data-chat-send-button class="btn relative size-11 shrink-0 px-0" aria-label="{{ __('ui.chat_send') }}"><x-icon name="transactions" wire:loading.remove wire:target="send" class="mx-auto size-5 rotate-90" /><span wire:loading wire:target="send" class="app-spinner absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2"></span></button>
            </div>
            <div data-chat-photo-queue wire:ignore hidden aria-live="polite" class="mt-2 max-h-24 space-y-1 overflow-y-auto"></div>
            <p class="mt-1 pl-1 text-[10px] text-dark/40">{{ __('ui.chat_photo_limit') }}</p>
        </form>
        @error('message') <p class="shrink-0 px-4 pb-3 text-xs text-red-600">{{ $message }}</p> @enderror
        @error('photos') <p class="shrink-0 px-4 pb-3 text-xs text-red-600">{{ $message }}</p> @enderror
        @error('photos.*') <p class="shrink-0 px-4 pb-3 text-xs text-red-600">{{ $message }}</p> @enderror
    </section>
    <div data-chat-image-viewer data-photo-position="{{ __('ui.chat_photo_position') }}" wire:ignore hidden role="dialog" aria-modal="true" aria-label="{{ __('ui.chat_image') }}" class="fixed inset-0 z-[100] bg-black/95">
        <button type="button" data-chat-image-close aria-label="{{ __('ui.close') }}" class="absolute left-4 top-4 z-10 grid size-11 place-items-center rounded-full bg-white/15 text-white"><x-icon name="back" class="size-5" /></button>
        <a data-chat-image-download href="#" aria-label="{{ __('ui.chat_photo_download') }}" title="{{ __('ui.chat_photo_download') }}" class="absolute right-4 top-4 z-10 grid size-11 place-items-center rounded-full bg-white/15 text-white"><x-icon name="download" class="size-5" /></a>
        <div data-chat-image-stage class="absolute inset-x-0 bottom-32 top-16 flex touch-pan-y items-center justify-center px-4">
            <img data-chat-image-full alt="{{ __('ui.chat_image') }}" class="max-h-full max-w-full object-contain">
        </div>
        <div class="absolute bottom-3 left-1/2 flex -translate-x-1/2 flex-col items-center">
            <p data-chat-image-position hidden class="mb-2 text-xs font-medium text-white/80"></p>
            <div data-chat-image-strip hidden class="flex max-w-[min(90vw,480px)] gap-2 overflow-x-auto rounded-xl bg-white/10 p-2"></div>
        </div>
    </div>
</div>
