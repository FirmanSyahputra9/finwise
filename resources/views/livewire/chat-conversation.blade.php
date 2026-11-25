<div wire:poll.5s class="h-dvh overflow-hidden bg-white">
    @php($person = $connection?->otherUser(auth()->id()))
    @php($isGroupAdmin = $group?->members->firstWhere('id', auth()->id())?->pivot->role === 'admin')
    <section class="mx-auto flex h-full min-h-0 w-full max-w-[430px] flex-col overflow-hidden bg-white" role="region" aria-label="{{ $group?->name ?? $person?->name }}">
        <header class="flex shrink-0 items-center gap-3 border-b border-dark/10 bg-white px-4 py-3">
            <a href="{{ route('chat') }}" wire:navigate aria-label="{{ __('ui.close') }}" class="grid size-10 shrink-0 place-items-center rounded-full bg-light text-dark"><x-icon name="back" class="size-5" /></a>
            @if ($group)
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-dark font-semibold text-mint">{{ mb_strtoupper(mb_substr($group->name, 0, 1)) }}</span>
            @else
                <span class="grid size-11 shrink-0 place-items-center overflow-hidden rounded-full bg-sky font-semibold text-white">@if ($person?->profile_photo_path)<img src="{{ asset($person->profile_photo_path) }}" alt="" class="h-full w-full object-cover">@else{{ mb_strtoupper(mb_substr($person?->name ?? '?', 0, 1)) }}@endif</span>
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

        <div id="chat-messages" class="min-h-0 flex-1 space-y-2 overflow-y-auto px-4 py-3">
            @forelse ($messages as $chatMessage)
                <div wire:key="chat-message-{{ $chatMessage->id }}" class="flex {{ $chatMessage->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                    <div class="flex max-w-[90%] items-start gap-2 {{ $chatMessage->sender_id === auth()->id() ? 'flex-row-reverse' : '' }}">
                        @if ($group)
                            <span class="grid size-8 shrink-0 place-items-center overflow-hidden rounded-full bg-sky text-[10px] font-semibold text-white">@if ($chatMessage->sender?->profile_photo_path)<img src="{{ asset($chatMessage->sender->profile_photo_path) }}" alt="" class="h-full w-full object-cover">@else{{ mb_strtoupper(mb_substr($chatMessage->sender?->name ?? '?', 0, 1)) }}@endif</span>
                        @endif
                        <div class="min-w-0">
                            @if ($group)<p class="mb-1 truncate text-[10px] font-medium text-dark/60 {{ $chatMessage->sender_id === auth()->id() ? 'text-right' : '' }}">{{ $chatMessage->sender?->name }}</p>@endif
                            <p class="whitespace-pre-wrap break-words rounded-2xl px-3 py-2 text-sm {{ $chatMessage->sender_id === auth()->id() ? ($group ? 'rounded-tr-md' : 'rounded-br-md').' bg-main text-dark' : ($group ? 'rounded-tl-md' : 'rounded-bl-md').' bg-light text-dark' }}">{{ $chatMessage->body }}<span class="mt-1 block text-right text-[10px] opacity-60">{{ $chatMessage->created_at->format('H:i') }}</span></p>
                        </div>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-dark/50">{{ __('ui.chat_first_message') }}</p>
            @endforelse
        </div>

        <form wire:submit="send" class="flex shrink-0 items-end gap-2 border-t border-dark/10 bg-white p-3">
            <textarea wire:model="message" rows="1" maxlength="2000" class="field max-h-28 min-h-11 resize-y flex-1" placeholder="{{ __('ui.chat_message_placeholder') }}"></textarea>
            <button class="btn relative size-11 shrink-0 px-0" aria-label="{{ __('ui.chat_send') }}"><x-icon name="transactions" wire:loading.remove wire:target="send" class="mx-auto size-5 rotate-90" /><span wire:loading wire:target="send" class="app-spinner absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2"></span></button>
        </form>
        @error('message') <p class="shrink-0 px-4 pb-3 text-xs text-red-600">{{ $message }}</p> @enderror
    </section>
</div>
