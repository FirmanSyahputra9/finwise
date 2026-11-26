<div wire:poll.5s>
    <x-screen :title="__('ui.chat')" :subtitle="__('ui.chat_text_only')">
        <div class="space-y-3">
            <section class="card-3d rounded-2xl bg-white p-3">
                <label class="sr-only" for="chat-contact-search">{{ __('ui.chat_search_contacts') }}</label>
                <div class="flex items-center gap-2 rounded-xl bg-light px-3">
                    <x-icon name="search" class="size-4 shrink-0 text-dark/50" />
                    <input id="chat-contact-search" wire:model.live.debounce.250ms="contactSearch" type="search" class="h-11 min-w-0 flex-1 border-0 bg-transparent text-sm outline-none placeholder:text-dark/50" placeholder="{{ __('ui.chat_search_contacts') }}">
                </div>
            </section>

            <nav class="flex items-center gap-2" aria-label="{{ __('ui.chat') }}">
                <button type="button" wire:click="togglePanel('connect')" title="{{ __('ui.chat_connect_title') }}" aria-label="{{ __('ui.chat_connect_title') }}" aria-expanded="{{ $activePanel === 'connect' ? 'true' : 'false' }}" class="relative grid size-12 place-items-center rounded-2xl {{ $activePanel === 'connect' ? 'bg-dark text-white' : 'card-3d bg-white text-dark' }}"><x-icon name="user-plus" class="size-5" /></button>
                @if ($pending->isNotEmpty() || $outgoing->isNotEmpty())
                    <button type="button" wire:click="togglePanel('requests')" title="{{ __('ui.chat_requests') }}" aria-label="{{ __('ui.chat_requests') }}" aria-expanded="{{ $activePanel === 'requests' ? 'true' : 'false' }}" class="relative grid size-12 place-items-center rounded-2xl {{ $activePanel === 'requests' ? 'bg-dark text-white' : 'card-3d bg-white text-dark' }}"><x-icon name="inbox" class="size-5" />@if ($pending->isNotEmpty())<span class="absolute -right-1 -top-1 grid size-5 place-items-center rounded-full bg-red-600 text-[10px] font-semibold text-white">{{ $pending->count() }}</span>@endif</button>
                @endif
                <button type="button" wire:click="togglePanel('create-group')" title="{{ __('ui.chat_group_create') }}" aria-label="{{ __('ui.chat_group_create') }}" aria-expanded="{{ $activePanel === 'create-group' ? 'true' : 'false' }}" class="relative grid size-12 place-items-center rounded-2xl {{ $activePanel === 'create-group' ? 'bg-dark text-white' : 'card-3d bg-white text-dark' }}"><x-icon name="plus" class="size-5" /></button>
                @if ($groups->isNotEmpty())
                    <button type="button" wire:click="togglePanel('groups')" title="{{ __('ui.chat_groups') }}" aria-label="{{ __('ui.chat_groups') }}" aria-expanded="{{ $activePanel === 'groups' ? 'true' : 'false' }}" class="grid size-12 place-items-center rounded-2xl {{ $activePanel === 'groups' ? 'bg-dark text-white' : 'card-3d bg-white text-dark' }}"><x-icon name="users" class="size-5" /></button>
                @endif
            </nav>

            @if ($activePanel === 'connect')
                <section class="card-3d rounded-2xl bg-white p-4">
                    <h2 class="text-sm font-semibold text-dark">{{ __('ui.chat_connect_title') }}</h2>
                    <p class="mt-1 text-xs leading-relaxed text-dark/60">{{ __('ui.chat_connect_hint') }}</p>
                    <form wire:submit="connect" class="mt-3 space-y-2">
                        <input wire:model="accountId" type="text" inputmode="numeric" maxlength="20" class="field" placeholder="{{ __('ui.chat_account_placeholder') }}" autocomplete="off">
                        <button class="btn inline-flex w-full items-center justify-center gap-2 px-4"><span wire:loading wire:target="connect" class="app-spinner"></span>{{ __('ui.chat_connect') }}</button>
                    </form>
                    @error('accountId') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </section>
            @elseif ($activePanel === 'requests')
                <section class="card-3d rounded-2xl bg-white p-4">
                    @if ($pending->isNotEmpty())
                        <h2 class="text-sm font-semibold text-dark">{{ __('ui.chat_requests') }}</h2>
                        <div class="mt-3 space-y-2">
                            @foreach ($pending as $request)
                                @php($requester = $request->otherUser(auth()->id()))
                                <div wire:key="chat-request-{{ $request->id }}" class="rounded-xl bg-light p-3"><p class="truncate text-sm font-medium text-dark">{{ $requester?->name }}</p><div class="mt-2 flex gap-2"><button type="button" wire:click="respond({{ $request->id }}, true)" class="btn inline-flex flex-1 items-center justify-center gap-1 px-2 py-2 text-xs"><span wire:loading wire:target="respond" class="app-spinner size-3"></span>{{ __('ui.accept') }}</button><button type="button" wire:click="respond({{ $request->id }}, false)" class="btn-soft inline-flex flex-1 items-center justify-center gap-1 px-2 py-2 text-xs"><span wire:loading wire:target="respond" class="app-spinner size-3"></span>{{ __('ui.decline') }}</button></div></div>
                            @endforeach
                        </div>
                    @endif
                    @if ($outgoing->isNotEmpty())
                        <h2 class="mt-3 text-sm font-semibold text-dark">{{ __('ui.chat_waiting') }}</h2>
                        <div class="mt-2 space-y-1 text-xs text-dark/70">@foreach ($outgoing as $request)<p wire:key="chat-outgoing-{{ $request->id }}">{{ $request->otherUser(auth()->id())?->name }} · {{ __('ui.chat_pending') }}</p>@endforeach</div>
                    @endif
                </section>
            @elseif ($activePanel === 'create-group')
                <section class="card-3d rounded-2xl bg-white p-4">
                    <h2 class="text-sm font-semibold text-dark">{{ __('ui.chat_group_create') }}</h2>
                    <form wire:submit="createGroup" class="mt-3 space-y-3">
                        <input wire:model="groupName" maxlength="80" class="field" placeholder="{{ __('ui.chat_group_name') }}">
                        <div class="max-h-48 space-y-1 overflow-y-auto rounded-2xl bg-light p-2">
                            <p class="px-2 py-1 text-xs font-medium text-dark/60">{{ __('ui.chat_group_invite_hint') }} ({{ count($selectedInviteIds) }})</p>
                            @forelse ($filteredConnectedUsers as $invitePerson)
                                @php($selected = in_array($invitePerson->id, array_map('intval', $selectedInviteIds), true))
                                <button type="button" wire:key="group-invite-contact-{{ $invitePerson->id }}" wire:click="toggleInviteContact({{ $invitePerson->id }})" aria-pressed="{{ $selected ? 'true' : 'false' }}" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm hover:bg-white"><span class="grid size-5 place-items-center rounded border {{ $selected ? 'border-dark bg-dark text-white' : 'border-dark/30' }}">{{ $selected ? '✓' : '' }}</span><span class="min-w-0 flex-1 truncate text-ink">{{ $invitePerson->name }}</span></button>
                            @empty
                                <p class="p-3 text-sm text-dark/60">{{ __('ui.chat_group_no_contacts') }}</p>
                            @endforelse
                            @if ($selectedInviteIds)<button type="button" wire:click="clearInviteContacts" class="mt-1 w-full border-t border-dark/10 px-3 py-2 text-left text-xs font-medium text-blue">{{ __('ui.clear_selection') }}</button>@endif
                        </div>
                        @error('groupName')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        @error('selectedInviteIds')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        <button class="btn w-full">{{ __('ui.chat_group_create') }}</button>
                    </form>
                </section>
            @elseif ($activePanel === 'groups')
                <section class="card-3d rounded-2xl bg-white p-4">
                    <h2 class="text-sm font-semibold text-dark">{{ __('ui.chat_groups') }}</h2>
                    <div class="mt-3 space-y-2">@foreach ($groups as $item)<button type="button" wire:key="chat-group-{{ $item->id }}" wire:click="openGroup({{ $item->id }})" class="flex w-full items-center gap-3 rounded-2xl bg-light p-3 text-left"><span class="grid size-10 shrink-0 place-items-center rounded-full bg-dark font-semibold text-mint">{{ mb_strtoupper(mb_substr($item->name, 0, 1)) }}</span><span class="min-w-0 flex-1 truncate text-sm font-medium text-dark">{{ $item->name }}</span><span class="text-xs text-dark/60">{{ $item->members_count }} {{ __('ui.chat_group_members_short') }}</span></button>@endforeach</div>
                </section>
            @endif

            <section class="card-3d rounded-2xl bg-white p-3">
                <h2 class="mb-2 text-sm font-semibold text-dark">{{ __('ui.chat_conversations') }}</h2>
                @if ($filteredConnectedUsers->isEmpty() && $filteredGroups->isEmpty())
                    <p class="py-3 text-center text-sm text-dark/60">{{ $contactSearch === '' ? __('ui.chat_empty') : __('ui.chat_no_search_results') }}</p>
                @else
                    <div class="max-h-[48dvh] space-y-2 overflow-y-auto">
                        @foreach ($filteredGroups as $item)
                            <button type="button" wire:key="chat-search-group-{{ $item->id }}" wire:click="openGroup({{ $item->id }})" class="flex w-full items-center gap-3 rounded-2xl bg-light p-3 text-left">
                                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-dark font-semibold text-mint">{{ mb_strtoupper(mb_substr($item->name, 0, 1)) }}</span>
                                <span class="min-w-0 flex-1 truncate text-sm font-medium text-dark">{{ $item->name }}<span class="ml-2 text-[10px] font-normal text-dark/50">{{ __('ui.chat_groups') }}</span></span>
                                <x-icon name="chat" class="size-4 text-nav" />
                            </button>
                        @endforeach
                        @foreach ($connections as $item)
                            @php($person = $item->otherUser(auth()->id()))
                            @if ($person && $filteredConnectedUsers->contains('id', $person->id))
                                <button type="button" wire:key="chat-person-{{ $item->id }}" wire:click="open({{ $item->id }})" class="flex w-full items-center gap-3 rounded-2xl bg-light p-3 text-left"><span class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-full bg-sky font-semibold text-white">@if ($person->profile_photo_path)<img src="{{ Storage::disk('public')->url($person->profile_photo_path) }}" alt="" class="h-full w-full object-cover">@else{{ mb_strtoupper(mb_substr($person->name ?? '?', 0, 1)) }}@endif</span><span class="min-w-0 flex-1 truncate text-sm font-medium text-dark">{{ $person->name }}</span><x-icon name="chat" class="size-4 text-nav" /></button>
                            @endif
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </x-screen>
</div>

