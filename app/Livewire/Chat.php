<?php

namespace App\Livewire;

use App\Models\ChatConnection;
use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Chat extends Component
{
    public string $accountId = '';
    public string $message = '';
    public ?int $selectedConnection = null;
    public ?int $selectedGroup = null;
    public string $groupName = '';
    public array $selectedInviteIds = [];
    public string $contactSearch = '';
    public ?int $inviteUserId = null;
    public string $notice = '';
    public string $activePanel = '';

    public function togglePanel(string $panel): void
    {
        abort_unless(in_array($panel, ['connect', 'requests', 'create-group', 'groups'], true), 404);
        $this->activePanel = $this->activePanel === $panel ? '' : $panel;
    }

    public function mount(?string $type = null, ?int $id = null): void
    {
        if ($type === null && $id === null) {
            return;
        }

        abort_unless($type !== null && $id !== null, 404);
        if ($type === 'direct') {
            $this->authorizedConnection($id);
            $this->selectedConnection = $id;
            return;
        }

        abort_unless($type === 'group', 404);
        $this->authorizedGroup($id);
        $this->selectedGroup = $id;
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
            $this->redirectRoute('chat.conversation', ['type' => 'direct', 'id' => $connection->id], navigate: true);
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
            $this->redirectRoute('chat.conversation', ['type' => 'direct', 'id' => $connection->id], navigate: true);
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
        $this->redirectRoute('chat.conversation', ['type' => 'direct', 'id' => $connectionId], navigate: true);
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
        $this->redirectRoute('chat.conversation', ['type' => 'group', 'id' => $group->id], navigate: true);
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
        $this->redirectRoute('chat.conversation', ['type' => 'group', 'id' => $groupId], navigate: true);
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

    public function disconnect(int $connectionId): void
    {
        $connection = $this->authorizedConnection($connectionId);
        DB::transaction(function () use ($connection) {
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
        $this->validate(['message' => ['required', 'string', 'max:2000']]);
        if (! $this->selectedConnection && ! $this->selectedGroup) {
            throw ValidationException::withMessages(['message' => __('ui.chat_choose_first')]);
        }

        $data = [
            'sender_id' => auth()->id(),
            'body' => trim($this->message),
        ];
        if ($this->selectedConnection) {
            $this->authorizedConnection($this->selectedConnection);
            $data['connection_id'] = $this->selectedConnection;
        } else {
            $this->authorizedGroup($this->selectedGroup);
            $data['group_id'] = $this->selectedGroup;
        }
        ChatMessage::create($data);
        $this->reset('message');
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
        if (! preg_match('/^(\d{12})(\d+)$/', $accountId, $matches)) return null;

        try {
            $second = Carbon::createFromFormat('!siHdmy', $matches[1]);
        } catch (\Throwable) {
            return null;
        }
        if (! $second) return null;

        $sequence = (int) $matches[2];
        if ($sequence < 1) return null;

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
        $messages = $connection
            ? $connection->messages()->with('sender')->latest()->limit(300)->get()->reverse()->values()
            : ($group ? $group->messages()->with('sender')->latest()->limit(300)->get()->reverse()->values() : collect());
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

        return view($view, compact('connections', 'pending', 'outgoing', 'connection', 'group', 'groups', 'messages', 'connectedUsers', 'filteredConnectedUsers', 'filteredGroups', 'inviteableUsers'));
    }
}
