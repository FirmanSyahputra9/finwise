<?php

namespace App\Livewire;

use App\Models\ChatConnection;
use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Models\ChatReadPosition;
use App\Models\User;
use App\Support\ChatConversationUuid;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Chat extends Component
{
    use WithFileUploads;

    public string $accountId = '';

    public string $message = '';

    /** @var array<int, TemporaryUploadedFile> */
    public array $photos = [];

    public bool $showStickerPicker = false;

    public ?int $selectedConnection = null;

    public ?int $selectedGroup = null;

    public string $groupName = '';

    public array $selectedInviteIds = [];

    public string $contactSearch = '';

    public ?int $inviteUserId = null;

    public string $notice = '';

    public string $activePanel = '';

    public int $visibleMessageCount = 50;

    public int $olderMessageCount = 0;

    public ?int $openedAtUnreadMessageId = null;

    public bool $unreadMarkerVisible = true;

    public function togglePanel(string $panel): void
    {
        abort_unless(in_array($panel, ['connect', 'requests', 'create-group', 'groups'], true), 404);
        $this->activePanel = $this->activePanel === $panel ? '' : $panel;
    }

    public function mount(?string $type = null, ?string $id = null): void
    {
        if ($type === null && $id === null) {
            return;
        }

        abort_unless($type !== null && $id !== null && Str::isUuid($id), 404);
        if ($type === 'direct') {
            $connection = ChatConnection::query()
                ->where('status', 'accepted')
                ->where(fn ($query) => $query->where('user_one_id', auth()->id())->orWhere('user_two_id', auth()->id()))
                ->get()
                ->first(fn (ChatConnection $item) => hash_equals(ChatConversationUuid::from('direct', (int) $item->id), strtolower($id)));
            abort_unless($connection, 404);
            $this->selectedConnection = $connection->id;
            $this->purgeExpiredMessages($connection);
            $this->openedAtUnreadMessageId = $this->findFirstUnreadMessageId($connection);

            return;
        }

        abort_unless($type === 'group', 404);
        $group = ChatGroup::query()
            ->whereHas('members', fn ($query) => $query->where('users.id', auth()->id()))
            ->get()
            ->first(fn (ChatGroup $item) => hash_equals(ChatConversationUuid::from('group', (int) $item->id), strtolower($id)));
        abort_unless($group, 404);
        $this->selectedGroup = $group->id;
        $this->purgeExpiredMessages($group);
        $this->openedAtUnreadMessageId = $this->findFirstUnreadMessageId($group);
    }

    public function connect(): void
    {
        $this->validate(['accountId' => ['required', 'string', 'regex:/^\d{13,}$/']]);
        $target = $this->findUserByAccountId(trim($this->accountId));
        if (! $target || $target->is(auth()->user())) {
            $this->addError('accountId', __('ui.chat_user_not_found'));

            return;
        }

        $me = (int) auth()->id();
        $first = min($me, (int) $target->id);
        $second = max($me, (int) $target->id);
        $connection = ChatConnection::firstOrCreate(
            ['user_one_id' => $first, 'user_two_id' => $second],
            ['requested_by' => $me, 'status' => 'pending'],
        );

        if ($connection->status === 'accepted') {
            $this->redirectRoute('chat.conversation', ['type' => 'direct', 'id' => ChatConversationUuid::from('direct', $connection->id)], navigate: true);
            $this->notice = __('ui.chat_already_connected');
        } elseif ($connection->requested_by === $me) {
            $this->notice = __('ui.chat_request_sent');
        } else {
            $this->notice = __('ui.chat_request_received');
        }

        $this->reset('accountId');
        $this->activePanel = 'requests';
        $this->dispatch('app-toast', message: $this->notice);
    }

    public function respond(int $connectionId, bool $accept): void
    {
        $connection = ChatConnection::query()->whereKey($connectionId)
            ->where('status', 'pending')
            ->where(function ($query) {
                $query->where('user_one_id', auth()->id())->orWhere('user_two_id', auth()->id());
            })->firstOrFail();

        if ($connection->requested_by === auth()->id()) {
            abort(403);
        }

        if ($accept) {
            $connection->update(['status' => 'accepted']);
            $this->redirectRoute('chat.conversation', ['type' => 'direct', 'id' => ChatConversationUuid::from('direct', $connection->id)], navigate: true);
            $this->notice = __('ui.chat_connected');
        } else {
            $connection->delete();
            $this->notice = __('ui.chat_request_declined');
        }
        $this->dispatch('app-toast', message: $this->notice);
    }

    public function open(int $connectionId): void
    {
        $this->authorizedConnection($connectionId);
        $this->selectedConnection = $connectionId;
        $this->selectedGroup = null;
        $this->redirectRoute('chat.conversation', ['type' => 'direct', 'id' => ChatConversationUuid::from('direct', $connectionId)], navigate: true);
    }

    public function createGroup(): void
    {
        $this->groupName = trim($this->groupName);
        $data = $this->validate([
            'groupName' => ['required', 'string', 'max:80'],
            'selectedInviteIds' => ['array', 'max:30'],
            'selectedInviteIds.*' => ['integer', 'distinct'],
        ]);

        $allowedIds = $this->connectedUsers()->modelKeys();
        $inviteIds = collect($data['selectedInviteIds'])->map(fn ($id) => (int) $id)->unique()->values();
        if ($inviteIds->diff($allowedIds)->isNotEmpty()) {
            $this->addError('selectedInviteIds', __('ui.chat_group_invite_connected_only'));

            return;
        }

        $group = DB::transaction(function () use ($data, $inviteIds) {
            $group = ChatGroup::create(['name' => $data['groupName'], 'creator_id' => auth()->id()]);
            $group->members()->attach(auth()->id(), ['role' => 'admin']);
            if ($inviteIds->isNotEmpty()) {
                $group->members()->attach($inviteIds->mapWithKeys(fn ($id) => [$id => ['role' => 'member']])->all());
            }

            return $group;
        });

        $this->selectedGroup = $group->id;
        $this->selectedConnection = null;
        $this->reset('groupName', 'selectedInviteIds');
        $this->dispatch('app-toast', message: __('ui.chat_group_created'));
        $this->redirectRoute('chat.conversation', ['type' => 'group', 'id' => ChatConversationUuid::from('group', $group->id)], navigate: true);
    }

    public function toggleInviteContact(int $userId): void
    {
        abort_unless($this->connectedUsers()->contains('id', $userId), 403);
        $selected = collect($this->selectedInviteIds)->map(fn ($id) => (int) $id)->all();
        $this->selectedInviteIds = in_array($userId, $selected, true)
            ? array_values(array_diff($selected, [$userId]))
            : [...$selected, $userId];
    }

    public function clearInviteContacts(): void
    {
        $this->selectedInviteIds = [];
    }

    public function openGroup(int $groupId): void
    {
        $this->authorizedGroup($groupId);
        $this->authorizedGroup($groupId);
        $this->selectedGroup = $groupId;
        $this->selectedConnection = null;
        $this->redirectRoute('chat.conversation', ['type' => 'group', 'id' => ChatConversationUuid::from('group', $groupId)], navigate: true);
    }

    public function inviteToGroup(int $groupId): void
    {
        $group = $this->authorizedGroup($groupId, admin: true);
        $data = $this->validate(['inviteUserId' => ['required', 'integer']]);
        $userId = (int) $data['inviteUserId'];
        abort_unless($this->connectedUsers()->contains('id', $userId), 403);
        abort_if($group->members()->whereKey($userId)->exists(), 409);
        $group->members()->attach($userId, ['role' => 'member']);
        $this->reset('inviteUserId');
        $this->dispatch('app-toast', message: __('ui.chat_group_invited'));
    }

    public function removeGroupMember(int $groupId, int $userId): void
    {
        $group = $this->authorizedGroup($groupId, admin: true);
        $member = $group->members()->whereKey($userId)->firstOrFail();
        if ($member->pivot->role === 'admin' && $group->members()->wherePivot('role', 'admin')->count() <= 1) {
            $this->addError('groupMembers', __('ui.chat_group_last_admin'));

            return;
        }
        $group->members()->detach($userId);
        if ($userId === (int) auth()->id()) {
            $this->redirectRoute('chat', navigate: true);
        }
    }

    public function deleteGroup(int $groupId): void
    {
        $group = $this->authorizedGroup($groupId, admin: true);
        ChatMessage::query()->where('group_id', $group->id)->get()->each(fn (ChatMessage $message) => $message->deleteAttachmentFile());
        $group->delete();
        if ($this->selectedGroup === $groupId) {
            $this->redirectRoute('chat', navigate: true);
        }
        $this->dispatch('app-toast', message: __('ui.chat_group_deleted'));
    }

    public function close(): void
    {
        $this->reset('selectedConnection', 'selectedGroup', 'message', 'inviteUserId');
        $this->redirectRoute('chat', navigate: true);
    }

    public function loadOlderMessages(): void
    {
        if ($this->openedAtUnreadMessageId !== null) {
            $this->olderMessageCount = min($this->olderMessageCount + 50, 1000);

            return;
        }

        $this->visibleMessageCount = min($this->visibleMessageCount + 50, 1000);
    }

    public function loadNewerMessages(): void
    {
        $this->visibleMessageCount = min($this->visibleMessageCount + 50, 1000);
    }

    /** @param array<int, int|string> $messageIds */
    public function markMessagesRead(array $messageIds): void
    {
        if (! $this->selectedConnection && ! $this->selectedGroup) {
            return;
        }

        $conversation = $this->selectedConnection
            ? $this->authorizedConnection($this->selectedConnection)
            : $this->authorizedGroup((int) $this->selectedGroup);
        $ids = collect($messageIds)->take(100)->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return;
        }

        $lastVisibleIncomingId = $conversation->messages()
            ->whereIn('id', $ids)
            ->where('sender_id', '!=', auth()->id())
            ->max('id');
        if (! $lastVisibleIncomingId) {
            return;
        }

        $identity = ['user_id' => auth()->id()];
        $identity[$this->selectedConnection ? 'connection_id' : 'group_id'] = $conversation->id;
        $position = ChatReadPosition::query()->firstOrNew($identity);
        if ((int) ($position->last_read_message_id ?? 0) < (int) $lastVisibleIncomingId) {
            $position->last_read_message_id = $lastVisibleIncomingId;
            $position->save();
        }

        if ($this->openedAtUnreadMessageId !== null && $lastVisibleIncomingId >= $this->openedAtUnreadMessageId) {
            $this->unreadMarkerVisible = false;
        }
    }

    private function findFirstUnreadMessageId(ChatConnection|ChatGroup $conversation): ?int
    {
        $identity = ['user_id' => auth()->id()];
        $identity[$conversation instanceof ChatConnection ? 'connection_id' : 'group_id'] = $conversation->id;
        $lastReadMessageId = ChatReadPosition::query()->where($identity)->value('last_read_message_id') ?? 0;

        return $conversation->messages()->where('sender_id', '!=', auth()->id())
            ->where('id', '>', $lastReadMessageId)->orderBy('id')->value('id');
    }

    public function updateRetentionDays(int $days): void
    {
        abort_unless(in_array($days, [1, 3, 5, 7, 15, 30], true), 422);
        if ($this->selectedConnection) {
            $conversation = $this->authorizedConnection($this->selectedConnection);
        } else {
            $conversation = $this->authorizedGroup((int) $this->selectedGroup, admin: true);
        }
        $conversation->update(['retention_days' => $days]);
        $this->dispatch('app-toast', message: __('ui.chat_retention_updated'));
    }

    private function purgeExpiredMessages(ChatConnection|ChatGroup $conversation): void
    {
        $cutoff = now()->subDays((int) ($conversation->retention_days ?: 30));
        $conversation->messages()->where('created_at', '<=', $cutoff)->get()->each(function (ChatMessage $message) {
            $message->deleteAttachmentFile();
            $message->delete();
        });
    }

    public function disconnect(int $connectionId): void
    {
        $connection = $this->authorizedConnection($connectionId);
        DB::transaction(function () use ($connection) {
            ChatMessage::where('connection_id', $connection->id)->get()->each(fn (ChatMessage $message) => $message->deleteAttachmentFile());
            ChatMessage::where('connection_id', $connection->id)->delete();
            $connection->delete();
        });
        $this->reset('selectedConnection', 'message');
        $this->notice = __('ui.chat_disconnected');
        $this->dispatch('app-toast', message: $this->notice);
        $this->redirectRoute('chat', navigate: true);
    }

    public function send(): void
    {
        $this->message = trim($this->message);
        $this->validate([
            'message' => ['nullable', 'string', 'max:2000'],
            'photos' => ['array', 'max:20'],
            'photos.*' => ['image', 'mimes:webp', 'max:5120'],
        ]);
        if ($this->message === '' && $this->photos === []) {
            throw ValidationException::withMessages(['message' => __('ui.chat_message_or_photo_required')]);
        }
        if (! $this->selectedConnection && ! $this->selectedGroup) {
            throw ValidationException::withMessages(['message' => __('ui.chat_choose_first')]);
        }

        if ($this->selectedConnection) {
            $this->authorizedConnection($this->selectedConnection);
        } else {
            $this->authorizedGroup($this->selectedGroup);
        }

        $body = $this->message;
        if ($this->photos !== []) {
            $paths = [];
            foreach ($this->photos as $photo) {
                $extension = strtolower($photo->extension() ?: '');
                abort_unless($extension === 'webp', 422);
                $path = 'chat-attachments/'.Str::uuid().'.webp';
                $storedPath = $photo->storeAs('chat-attachments', basename($path), 'local');
                abort_unless($storedPath !== false, 500);
                $paths[] = $storedPath;
            }
            $body = json_encode([
                '__finwise_attachment' => 'images',
                'items' => $paths,
                'caption' => $this->message,
            ], JSON_THROW_ON_ERROR);
        }

        $data = [
            'sender_id' => auth()->id(),
            'body' => $body,
        ];
        if ($this->selectedConnection) {
            $data['connection_id'] = $this->selectedConnection;
        } else {
            $data['group_id'] = $this->selectedGroup;
        }
        ChatMessage::create($data);
        $this->reset('message', 'photos');
        $this->dispatch('chat-photos-sent');
    }

    public function toggleStickerPicker(): void
    {
        $this->showStickerPicker = ! $this->showStickerPicker;
    }

    public function sendSticker(string $sticker): void
    {
        abort_unless(in_array($sticker, ChatMessage::STICKERS, true), 422);
        if (! $this->selectedConnection && ! $this->selectedGroup) {
            throw ValidationException::withMessages(['message' => __('ui.chat_choose_first')]);
        }

        $data = ['sender_id' => auth()->id(), 'body' => ChatMessage::STICKER_PREFIX.$sticker];
        if ($this->selectedConnection) {
            $this->authorizedConnection($this->selectedConnection);
            $data['connection_id'] = $this->selectedConnection;
        } else {
            $this->authorizedGroup((int) $this->selectedGroup);
            $data['group_id'] = $this->selectedGroup;
        }

        ChatMessage::create($data);
        $this->showStickerPicker = false;
    }

    private function authorizedConnection(int $id): ChatConnection
    {
        return ChatConnection::query()->whereKey($id)->where('status', 'accepted')
            ->where(function ($query) {
                $query->where('user_one_id', auth()->id())->orWhere('user_two_id', auth()->id());
            })->firstOrFail();
    }

    private function authorizedGroup(int $id, bool $admin = false): ChatGroup
    {
        $group = ChatGroup::query()->whereKey($id)
            ->whereHas('members', fn ($query) => $query->where('users.id', auth()->id()))
            ->with('members')
            ->firstOrFail();

        if ($admin && $group->members->firstWhere('id', auth()->id())?->pivot->role !== 'admin') {
            abort(403);
        }

        return $group;
    }

    private function connectedUsers()
    {
        return ChatConnection::query()
            ->where('status', 'accepted')
            ->where(fn ($query) => $query->where('user_one_id', auth()->id())->orWhere('user_two_id', auth()->id()))
            ->with(['userOne', 'userTwo'])
            ->get()
            ->map(fn (ChatConnection $connection) => $connection->otherUser((int) auth()->id()))
            ->filter()
            ->unique('id')
            ->values();
    }

    private function findUserByAccountId(string $accountId): ?User
    {
        if (! preg_match('/^(\d{12})(\d+)$/', $accountId, $matches)) {
            return null;
        }

        try {
            $second = Carbon::createFromFormat('!siHdmy', $matches[1]);
        } catch (\Throwable) {
            return null;
        }
        if (! $second) {
            return null;
        }

        $sequence = (int) $matches[2];
        if ($sequence < 1) {
            return null;
        }

        return User::query()->where('created_at', '>=', $second)
            ->where('created_at', '<', $second->copy()->addSecond())
            ->orderBy('id')->skip($sequence - 1)->first();
    }

    public function render()
    {
        $me = (int) auth()->id();
        $connections = ChatConnection::query()->where('status', 'accepted')
            ->where(fn ($query) => $query->where('user_one_id', $me)->orWhere('user_two_id', $me))
            ->with(['userOne', 'userTwo'])->withMax('messages', 'created_at')
            ->orderByDesc('messages_max_created_at')->get();
        $pending = ChatConnection::query()->where('status', 'pending')
            ->where('requested_by', '!=', $me)
            ->where(fn ($query) => $query->where('user_one_id', $me)->orWhere('user_two_id', $me))
            ->with(['userOne', 'userTwo'])->latest()->get();
        $outgoing = ChatConnection::query()->where('status', 'pending')->where('requested_by', $me)
            ->where(fn ($query) => $query->where('user_one_id', $me)->orWhere('user_two_id', $me))
            ->with(['userOne', 'userTwo'])->latest()->get();

        $groups = ChatGroup::query()->whereHas('members', fn ($query) => $query->where('users.id', $me))
            ->withCount('members')->withMax('messages', 'created_at')
            ->orderByDesc('messages_max_created_at')->get();

        $connection = $this->selectedConnection
            ? $connections->firstWhere('id', $this->selectedConnection)
            : null;
        $group = $this->selectedGroup ? $groups->firstWhere('id', $this->selectedGroup) : null;
        $group?->load('members');
        $messageQuery = $connection?->messages() ?? $group?->messages();
        $totalMessageCount = $messageQuery?->count() ?? 0;
        $unreadMessageId = null;
        $hasOlderMessages = false;
        $hasNewerMessages = false;
        if ($messageQuery && $this->openedAtUnreadMessageId !== null) {
            $anchorExists = (clone $messageQuery)->whereKey($this->openedAtUnreadMessageId)
                ->where('sender_id', '!=', $me)->exists();
            if ($anchorExists) {
                $unreadMessageId = $this->unreadMarkerVisible ? $this->openedAtUnreadMessageId : null;
                $olderQuery = (clone $messageQuery)->where('id', '<', $this->openedAtUnreadMessageId);
                $olderTotal = (clone $olderQuery)->count();
                $olderMessages = $this->olderMessageCount > 0
                    ? $olderQuery->with('sender')->latest()->limit($this->olderMessageCount)->get()->reverse()->values()
                    : collect();
                $unreadMessages = (clone $messageQuery)->where('id', '>=', $this->openedAtUnreadMessageId)
                    ->with('sender')->orderBy('id')->limit($this->visibleMessageCount)->get();
                $latestMessages = (clone $messageQuery)->with('sender')->latest()->limit(50)->get()->reverse()->values();
                $messages = $olderMessages->concat($unreadMessages)->concat($latestMessages)->unique('id')->sortBy('id')->values();
                $hasOlderMessages = $this->olderMessageCount < $olderTotal;
                $lastLoadedAfterUnreadId = $unreadMessages->last()?->id ?? $this->openedAtUnreadMessageId;
                $hasNewerMessages = (clone $messageQuery)->where('id', '>', $lastLoadedAfterUnreadId)
                    ->whereNotIn('id', $latestMessages->pluck('id'))->exists();
            } else {
                $this->openedAtUnreadMessageId = null;
            }
        }

        if ($messageQuery && $this->openedAtUnreadMessageId === null) {
            $messages = (clone $messageQuery)->with('sender')->latest()->limit($this->visibleMessageCount)->get()->reverse()->values();
            $hasOlderMessages = $messages->count() < $totalMessageCount;
        }
        $messages ??= collect();
        $connectedUsers = $this->connectedUsers();
        $filteredConnectedUsers = trim($this->contactSearch) === ''
            ? $connectedUsers
            : $connectedUsers->filter(fn (User $user) => mb_stripos($user->name, trim($this->contactSearch)) !== false)->values();
        $filteredGroups = trim($this->contactSearch) === ''
            ? $groups
            : $groups->filter(fn (ChatGroup $chatGroup) => mb_stripos($chatGroup->name, trim($this->contactSearch)) !== false)->values();
        $inviteableUsers = $group
            ? $connectedUsers->reject(fn (User $user) => $group->members->contains('id', $user->id))->values()
            : collect();

        $view = $connection || $group ? 'livewire.chat-conversation' : 'livewire.chat';

        return view($view, compact('connections', 'pending', 'outgoing', 'connection', 'group', 'groups', 'messages', 'totalMessageCount', 'unreadMessageId', 'hasOlderMessages', 'hasNewerMessages', 'connectedUsers', 'filteredConnectedUsers', 'filteredGroups', 'inviteableUsers'));
    }
}
