<?php

use App\Livewire\Chat;
use App\Models\ChatConnection;
use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('sends a connection request using the formatted account ID', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $timestamp = Carbon::parse('2026-11-24 14:32:18');
    $sender->forceFill(['created_at' => $timestamp, 'updated_at' => $timestamp])->save();
    $receiver->forceFill(['created_at' => $timestamp, 'updated_at' => $timestamp])->save();

    Livewire::actingAs($sender)
        ->test(Chat::class)
        ->set('accountId', $receiver->profileId())
        ->call('connect')
        ->assertHasNoErrors()
        ->assertSee(__('ui.chat_waiting'))
        ->assertDispatched('app-toast');

    expect(ChatConnection::query()->where('requested_by', $sender->id)->where('status', 'pending')->exists())->toBeTrue();
});

it('allows text chat only after the recipient accepts the connection', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $receiver->forceFill(['profile_photo_path' => 'profile-photos/'.$receiver->id.'/avatar.webp'])->save();
    $connection = ChatConnection::create([
        'user_one_id' => min($sender->id, $receiver->id),
        'user_two_id' => max($sender->id, $receiver->id),
        'requested_by' => $sender->id,
        'status' => 'pending',
    ]);

    Livewire::actingAs($sender)
        ->test(Chat::class)
        ->set('selectedConnection', $connection->id)
        ->set('message', 'Halo')
        ->call('send')
        ->assertNotFound();

    Livewire::actingAs($receiver)
        ->test(Chat::class)
        ->call('respond', $connection->id, true)
        ->assertHasNoErrors();

    Livewire::actingAs($sender)
        ->test(Chat::class)
        ->set('selectedConnection', $connection->id)
        ->set('message', 'Halo')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSee('role="region"', false)
        ->assertSee(asset($receiver->profile_photo_path), false)
        ->call('close')
        ->assertSet('selectedConnection', null);

    $savedMessage = ChatMessage::query()->where('connection_id', $connection->id)->firstOrFail();
    expect($savedMessage->body)->toBe('Halo')
        ->and(DB::table('chat_messages')->where('id', $savedMessage->id)->value('body'))->not->toBe('Halo');
});

it('lets a connected user disconnect and removes that connection history', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $connection = ChatConnection::create([
        'user_one_id' => min($owner->id, $other->id),
        'user_two_id' => max($owner->id, $other->id),
        'requested_by' => $owner->id,
        'status' => 'accepted',
    ]);
    ChatMessage::create(['connection_id' => $connection->id, 'sender_id' => $owner->id, 'body' => 'Sampai jumpa']);

    Livewire::actingAs($other)
        ->test(Chat::class)
        ->set('selectedConnection', $connection->id)
        ->call('disconnect', $connection->id)
        ->assertHasNoErrors()
        ->assertDispatched('app-toast');

    expect(ChatConnection::find($connection->id))->toBeNull()
        ->and(ChatMessage::where('connection_id', $connection->id)->exists())->toBeFalse();

    Livewire::actingAs($owner)
        ->test(Chat::class)
        ->call('open', $connection->id)
        ->assertNotFound();
});

it('creates a group with connected contacts and allows members to send encrypted group messages', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create(['name' => 'Mira Putri']);
    $member->forceFill(['profile_photo_path' => 'profile-photos/'.$member->id.'/avatar.webp'])->save();
    $connection = ChatConnection::create([
        'user_one_id' => min($admin->id, $member->id),
        'user_two_id' => max($admin->id, $member->id),
        'requested_by' => $admin->id,
        'status' => 'accepted',
    ]);

    Livewire::actingAs($admin)
        ->test(Chat::class)
        ->set('groupName', 'Keluarga')
        ->call('toggleInviteContact', $member->id)
        ->call('createGroup')
        ->assertHasNoErrors();

    $group = ChatGroup::where('name', 'Keluarga')->firstOrFail();
    expect($group->members()->whereKey($admin->id)->first()->pivot->role)->toBe('admin')
        ->and($group->members()->whereKey($member->id)->exists())->toBeTrue();

    Livewire::actingAs($member)
        ->test(Chat::class)
        ->call('openGroup', $group->id)
        ->set('message', 'Pesan rahasia grup')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSee('Pesan rahasia grup')
        ->assertSee('Mira Putri')
        ->assertSee(asset($member->profile_photo_path), false);

    $message = ChatMessage::where('group_id', $group->id)->firstOrFail();
    expect($message->body)->toBe('Pesan rahasia grup')
        ->and(DB::table('chat_messages')->where('id', $message->id)->value('body'))->not->toBe('Pesan rahasia grup');
});

it('lets group admins manage members and delete the complete group history', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    ChatConnection::create([
        'user_one_id' => min($admin->id, $member->id),
        'user_two_id' => max($admin->id, $member->id),
        'requested_by' => $admin->id,
        'status' => 'accepted',
    ]);
    $group = ChatGroup::create(['name' => 'Teman', 'creator_id' => $admin->id]);
    $group->members()->attach($admin->id, ['role' => 'admin']);
    ChatMessage::create(['group_id' => $group->id, 'sender_id' => $admin->id, 'body' => 'Pesan grup']);

    Livewire::actingAs($member)->test(Chat::class)
        ->call('deleteGroup', $group->id)
        ->assertNotFound();

    Livewire::actingAs($admin)->test(Chat::class)
        ->set('inviteUserId', $member->id)
        ->call('inviteToGroup', $group->id)
        ->assertHasNoErrors();
    expect($group->members()->whereKey($member->id)->exists())->toBeTrue();

    Livewire::actingAs($admin)->test(Chat::class)
        ->call('removeGroupMember', $group->id, $member->id)
        ->assertHasNoErrors();
    expect($group->members()->whereKey($member->id)->exists())->toBeFalse();

    Livewire::actingAs($admin)->test(Chat::class)
        ->call('deleteGroup', $group->id)
        ->assertHasNoErrors();
    expect(ChatGroup::find($group->id))->toBeNull()
        ->and(ChatMessage::where('group_id', $group->id)->exists())->toBeFalse();
});

it('filters group invitation contacts by the entered name', function () {
    $owner = User::factory()->create();
    $mira = User::factory()->create(['name' => 'Mira Putri']);
    $budi = User::factory()->create(['name' => 'Budi Santoso']);
    foreach ([$mira, $budi] as $contact) {
        ChatConnection::create([
            'user_one_id' => min($owner->id, $contact->id),
            'user_two_id' => max($owner->id, $contact->id),
            'requested_by' => $owner->id,
            'status' => 'accepted',
        ]);
    }

    Livewire::actingAs($owner)
        ->test(Chat::class)
        ->set('contactSearch', 'mIrA')
        ->assertViewHas('filteredConnectedUsers', fn ($users) => $users->pluck('id')->all() === [$mira->id]);
});

it('opens one separate chat tools panel at a time', function () {
    Livewire::actingAs(User::factory()->create())->test(Chat::class)
        ->call('togglePanel', 'connect')
        ->assertSet('activePanel', 'connect')
        ->call('togglePanel', 'create-group')
        ->assertSet('activePanel', 'create-group')
        ->call('togglePanel', 'create-group')
        ->assertSet('activePanel', '');
});

it('searches the contact list and groups by their names', function () {
    $owner = User::factory()->create();
    $group = ChatGroup::create(['name' => 'Keluarga', 'creator_id' => $owner->id]);
    $group->members()->attach($owner->id, ['role' => 'admin']);
    $otherGroup = ChatGroup::create(['name' => 'Teman Lama', 'creator_id' => $owner->id]);
    $otherGroup->members()->attach($owner->id, ['role' => 'admin']);

    Livewire::actingAs($owner)->test(Chat::class)
        ->set('contactSearch', 'kelu')
        ->assertViewHas('filteredGroups', fn ($groups) => $groups->pluck('id')->all() === [$group->id])
        ->assertSee('Keluarga')
        ->assertDontSee('Teman Lama');
});

it('opens direct chats and groups on their own authorized routes', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $outsider = User::factory()->create();
    $connection = ChatConnection::create([
        'user_one_id' => min($owner->id, $other->id),
        'user_two_id' => max($owner->id, $other->id),
        'requested_by' => $owner->id,
        'status' => 'accepted',
    ]);
    $group = ChatGroup::create(['name' => 'Keluarga', 'creator_id' => $owner->id]);
    $group->members()->attach($owner->id, ['role' => 'admin']);

    $this->actingAs($owner)->get(route('chat.conversation', ['type' => 'direct', 'id' => $connection->id]))
        ->assertOk()->assertSee($other->name)
        ->assertDontSee('id="bottom-navigation"', false)
        ->assertDontSee('global-notifications', false);
    $this->actingAs($owner)->get(route('chat.conversation', ['type' => 'group', 'id' => $group->id]))
        ->assertOk()->assertSee('Keluarga');
    $this->actingAs($outsider)->get(route('chat.conversation', ['type' => 'direct', 'id' => $connection->id]))
        ->assertNotFound();
    $this->actingAs($outsider)->get(route('chat.conversation', ['type' => 'group', 'id' => $group->id]))
        ->assertNotFound();
});
