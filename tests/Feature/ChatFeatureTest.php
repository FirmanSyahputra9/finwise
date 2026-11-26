<?php

use App\Livewire\Chat;
use App\Models\ChatCall;
use App\Models\ChatCallSignal;
use App\Models\ChatConnection;
use App\Models\ChatGroup;
use App\Models\ChatMessage;
use App\Models\ChatReadPosition;
use App\Models\User;
use App\Support\ChatConversationUuid;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        ->assertSee(Storage::disk('public')->url($receiver->profile_photo_path), false)
        ->call('close')
        ->assertSet('selectedConnection', null);

    $savedMessage = ChatMessage::query()->where('connection_id', $connection->id)->firstOrFail();
    expect($savedMessage->body)->toBe('Halo')
        ->and(DB::table('chat_messages')->where('id', $savedMessage->id)->value('body'))->not->toBe('Halo');
    $this->actingAs($sender)->get(route('chat.conversation', [
        'type' => 'direct',
        'id' => ChatConversationUuid::from('direct', $connection->id),
    ]))->assertOk()
        ->assertSee('data-chat-local-time', false)
        ->assertSee($savedMessage->created_at->copy()->utc()->toIso8601String(), false)
        ->assertSee($savedMessage->created_at->copy()->timezone('Asia/Jakarta')->format('H:i'), false);
});

it('sends encrypted stickers only to an authorized chat', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $connection = ChatConnection::create([
        'user_one_id' => min($sender->id, $receiver->id),
        'user_two_id' => max($sender->id, $receiver->id),
        'requested_by' => $sender->id,
        'status' => 'accepted',
    ]);

    Livewire::actingAs($sender)
        ->test(Chat::class)
        ->set('selectedConnection', $connection->id)
        ->call('toggleStickerPicker')
        ->assertSee('😻')
        ->call('sendSticker', '😻')
        ->assertHasNoErrors()
        ->assertSee('😻')
        ->assertSee(__('ui.chat_stickers'));

    $stickerMessage = ChatMessage::query()->where('connection_id', $connection->id)->firstOrFail();
    expect($stickerMessage->sticker())->toBe('😻')
        ->and(DB::table('chat_messages')->where('id', $stickerMessage->id)->value('body'))->not->toContain('😻');

    Livewire::actingAs(User::factory()->create())
        ->test(Chat::class)
        ->set('selectedConnection', $connection->id)
        ->call('sendSticker', '😻')
        ->assertNotFound();

    Livewire::actingAs($sender)
        ->test(Chat::class)
        ->set('selectedConnection', $connection->id)
        ->call('sendSticker', 'not-a-sticker')
        ->assertStatus(422);
});

it('allows voice calls and exchanges encrypted WebRTC signals only between connected users', function () {
    $caller = User::factory()->create();
    $callee = User::factory()->create();
    $outsider = User::factory()->create();
    $connection = ChatConnection::create([
        'user_one_id' => min($caller->id, $callee->id),
        'user_two_id' => max($caller->id, $callee->id),
        'requested_by' => $caller->id,
        'status' => 'accepted',
    ]);
    $this->actingAs($caller)->get(route('chat.conversation', [
        'type' => 'direct', 'id' => ChatConversationUuid::from('direct', $connection->id),
    ]))->assertOk()->assertSee('data-voice-call-start', false)->assertSee('data-voice-call-manager', false);

    $this->actingAs($caller)->postJson(route('chat.calls.start', $connection->id))
        ->assertCreated()->assertJsonPath('status', 'ringing');
    $call = ChatCall::query()->firstOrFail();
    $this->actingAs($caller)->postJson(route('chat.calls.start', $connection->id))->assertConflict();

    $this->actingAs($callee)->getJson(route('chat.calls.incoming'))
        ->assertOk()->assertJsonPath('call.id', $call->uuid)->assertJsonPath('call.caller', $caller->name);
    $this->actingAs($callee)->postJson(route('chat.calls.accept', $call->uuid))
        ->assertOk()->assertJsonPath('status', 'accepted');

    $offer = ['type' => 'offer', 'sdp' => 'private-offer-sdp'];
    $this->actingAs($caller)->postJson(route('chat.calls.signal', $call->uuid), [
        'type' => 'offer', 'payload' => $offer,
    ])->assertCreated();

    $signal = ChatCallSignal::query()->firstOrFail();
    expect($signal->payload)->toBe($offer)
        ->and(DB::table('chat_call_signals')->where('id', $signal->id)->value('payload'))->not->toContain('private-offer-sdp');

    $this->actingAs($callee)->getJson(route('chat.calls.signals', ['uuid' => $call->uuid, 'after' => 0]))
        ->assertOk()->assertJsonPath('signals.0.type', 'offer')->assertJsonPath('signals.0.payload.sdp', 'private-offer-sdp');

    $this->actingAs($outsider)->getJson(route('chat.calls.signals', ['uuid' => $call->uuid, 'after' => 0]))->assertNotFound();
    $this->actingAs($outsider)->postJson(route('chat.calls.start', $connection->id))->assertNotFound();
});

it('returns an empty incoming-call response when there is no call to answer', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson(route('chat.calls.incoming'))
        ->assertOk()->assertExactJson(['call' => null]);
});

it('rejects calls from pending connections and prevents a third user from ending a call', function () {
    $caller = User::factory()->create();
    $callee = User::factory()->create();
    $outsider = User::factory()->create();
    $connection = ChatConnection::create([
        'user_one_id' => min($caller->id, $callee->id),
        'user_two_id' => max($caller->id, $callee->id),
        'requested_by' => $caller->id,
        'status' => 'pending',
    ]);

    $this->actingAs($caller)->postJson(route('chat.calls.start', $connection->id))->assertNotFound();
    $connection->update(['status' => 'accepted']);
    $this->actingAs($caller)->postJson(route('chat.calls.start', $connection->id))->assertCreated();
    $call = ChatCall::query()->firstOrFail();
    $this->actingAs($outsider)->postJson(route('chat.calls.end', $call->uuid))->assertNotFound();
    $this->actingAs($callee)->postJson(route('chat.calls.reject', $call->uuid))->assertOk()->assertJsonPath('status', 'rejected');
    $this->actingAs($caller)->getJson(route('chat.calls.signals', ['uuid' => $call->uuid, 'after' => 0]))
        ->assertOk()->assertJsonPath('status', 'rejected');
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
        ->assertSee(Storage::disk('public')->url($member->profile_photo_path), false);

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
    $directUuid = ChatConversationUuid::from('direct', $connection->id);
    $groupUuid = ChatConversationUuid::from('group', $group->id);

    expect($directUuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/')
        ->and($groupUuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');

    $this->actingAs($owner)->get(route('chat.conversation', ['type' => 'direct', 'id' => $directUuid]))
        ->assertOk()->assertSee($other->name)
        ->assertDontSee('\\n', false)
        ->assertDontSee('id="bottom-navigation"', false)
        ->assertDontSee('global-notifications', false);
    $this->actingAs($owner)->get(route('chat.conversation', ['type' => 'group', 'id' => $groupUuid]))
        ->assertOk()->assertSee('Keluarga');
    $this->actingAs($outsider)->get(route('chat.conversation', ['type' => 'direct', 'id' => $directUuid]))
        ->assertNotFound();
    $this->actingAs($outsider)->get(route('chat.conversation', ['type' => 'group', 'id' => $groupUuid]))
        ->assertNotFound();
    $this->actingAs($owner)->get('/chat/group/'.$group->id)->assertNotFound();
});

it('opens at the first unread incoming message and advances the participant read position', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $connection = ChatConnection::create([
        'user_one_id' => min($owner->id, $other->id),
        'user_two_id' => max($owner->id, $other->id),
        'requested_by' => $owner->id,
        'status' => 'accepted',
    ]);
    $alreadyRead = ChatMessage::create(['connection_id' => $connection->id, 'sender_id' => $other->id, 'body' => 'Sudah dibaca']);
    $unread = ChatMessage::create(['connection_id' => $connection->id, 'sender_id' => $other->id, 'body' => 'Belum dibaca']);
    $ownMessage = ChatMessage::create(['connection_id' => $connection->id, 'sender_id' => $owner->id, 'body' => 'Pesan saya']);
    $laterUnread = ChatMessage::create(['connection_id' => $connection->id, 'sender_id' => $other->id, 'body' => 'Pesan berikutnya']);
    $thirdPerson = User::factory()->create();
    $foreignConnection = ChatConnection::create([
        'user_one_id' => min($owner->id, $thirdPerson->id),
        'user_two_id' => max($owner->id, $thirdPerson->id),
        'requested_by' => $owner->id,
        'status' => 'accepted',
    ]);
    $foreignMessage = ChatMessage::create(['connection_id' => $foreignConnection->id, 'sender_id' => $thirdPerson->id, 'body' => 'Pesan lain']);

    ChatReadPosition::create([
        'user_id' => $owner->id,
        'connection_id' => $connection->id,
        'last_read_message_id' => $alreadyRead->id,
    ]);

    $component = Livewire::actingAs($owner)->test(Chat::class, [
        'type' => 'direct',
        'id' => ChatConversationUuid::from('direct', $connection->id),
    ])->assertViewHas('unreadMessageId', $unread->id)
        ->assertSee('Belum dibaca');

    $component->call('markMessagesRead', [$unread->id, $ownMessage->id]);
    expect(ChatReadPosition::query()->where('user_id', $owner->id)->where('connection_id', $connection->id)->value('last_read_message_id'))
        ->toBe($unread->id);

    Livewire::actingAs($owner)->test(Chat::class, [
        'type' => 'direct',
        'id' => ChatConversationUuid::from('direct', $connection->id),
    ])->assertViewHas('unreadMessageId', $laterUnread->id);

    $component->call('markMessagesRead', [$foreignMessage->id]);
    expect(ChatReadPosition::query()->where('user_id', $owner->id)->where('connection_id', $connection->id)->value('last_read_message_id'))
        ->toBe($unread->id);

    $group = ChatGroup::create(['name' => 'Grup baca', 'creator_id' => $owner->id]);
    $group->members()->attach($owner->id, ['role' => 'admin']);
    $group->members()->attach($thirdPerson->id, ['role' => 'member']);
    $groupUnread = ChatMessage::create(['group_id' => $group->id, 'sender_id' => $thirdPerson->id, 'body' => 'Pesan grup belum dibaca']);
    Livewire::actingAs($owner)->test(Chat::class, [
        'type' => 'group',
        'id' => ChatConversationUuid::from('group', $group->id),
    ])->assertViewHas('unreadMessageId', $groupUnread->id)
        ->call('markMessagesRead', [$groupUnread->id]);

    expect(ChatReadPosition::query()->where('user_id', $owner->id)->where('group_id', $group->id)->value('last_read_message_id'))
        ->toBe($groupUnread->id);
});

it('sends private encrypted photo messages and only serves them to conversation members', function () {
    Storage::fake('local');
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $outsider = User::factory()->create();
    $connection = ChatConnection::create([
        'user_one_id' => min($sender->id, $receiver->id),
        'user_two_id' => max($sender->id, $receiver->id),
        'requested_by' => $sender->id,
        'status' => 'accepted',
    ]);

    Livewire::actingAs($sender)->test(Chat::class, ['type' => 'direct', 'id' => ChatConversationUuid::from('direct', $connection->id)])
        ->set('photos', [UploadedFile::fake()->createWithContent('receipt.webp', base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEAAUAmJaQAA3AA/v89WAAAAA==', true))])
        ->set('message', 'Lihat ini')
        ->call('send')
        ->assertHasNoErrors();

    $message = ChatMessage::query()->where('connection_id', $connection->id)->firstOrFail();
    $attachment = $message->imageAttachment();
    expect($attachment)->not->toBeNull()
        ->and(str_ends_with($attachment['path'], '.webp'))->toBeTrue()
        ->and($attachment['caption'])->toBe('Lihat ini')
        ->and(DB::table('chat_messages')->where('id', $message->id)->value('body'))->not->toContain($attachment['path']);
    Storage::disk('local')->assertExists($attachment['path']);

    $attachmentUuid = ChatConversationUuid::from('attachment', $message->id);
    $this->actingAs($sender)->get(route('chat.conversation', [
        'type' => 'direct',
        'id' => ChatConversationUuid::from('direct', $connection->id),
    ]))->assertOk()
        ->assertSee(route('chat.attachment', $attachmentUuid), false)
        ->assertSee('data-image-index="0"', false)
        ->assertSee('data-chat-image-viewer', false);
    $this->actingAs($receiver)->get(route('chat.attachment', $attachmentUuid))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private');
    $this->actingAs($receiver)->get(route('chat.attachment', $attachmentUuid).'?download=1')
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="putraganteng-'.$attachmentUuid.'.webp"');
    $this->actingAs($outsider)->get(route('chat.attachment', $attachmentUuid))->assertNotFound();
    $this->actingAs($receiver)->get('/chat/attachments/'.$message->id)->assertNotFound();
    Livewire::actingAs($receiver)->test(Chat::class)->call('disconnect', $connection->id)->assertHasNoErrors();
    Storage::disk('local')->assertMissing($attachment['path']);
});

it('serves a group photo only to current group members and removes it with group history', function () {
    Storage::fake('local');
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $group = ChatGroup::create(['name' => 'Teman', 'creator_id' => $admin->id]);
    $group->members()->attach($admin->id, ['role' => 'admin']);
    $group->members()->attach($member->id, ['role' => 'member']);

    Livewire::actingAs($member)->test(Chat::class, ['type' => 'group', 'id' => ChatConversationUuid::from('group', $group->id)])
        ->set('photos', [UploadedFile::fake()->createWithContent('photo.webp', base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEAAUAmJaQAA3AA/v89WAAAAA==', true))])
        ->call('send')
        ->assertHasNoErrors();

    $message = ChatMessage::query()->where('group_id', $group->id)->firstOrFail();
    $attachment = $message->imageAttachment();
    expect($attachment)->not->toBeNull();
    $attachmentUuid = ChatConversationUuid::from('attachment', $message->id);
    $this->actingAs($admin)->get(route('chat.attachment', $attachmentUuid))->assertOk();
    $this->actingAs($outsider)->get(route('chat.attachment', $attachmentUuid))->assertNotFound();
    Livewire::actingAs($admin)->test(Chat::class)->call('deleteGroup', $group->id)->assertHasNoErrors();
    Storage::disk('local')->assertMissing($attachment['path']);
});

it('sends photo batches and shows three photos plus an overflow tile after four', function () {
    Storage::fake('local');
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $connection = ChatConnection::create([
        'user_one_id' => min($sender->id, $receiver->id),
        'user_two_id' => max($sender->id, $receiver->id),
        'requested_by' => $sender->id,
        'status' => 'accepted',
    ]);
    $webp = base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEAAUAmJaQAA3AA/v89WAAAAA==', true);
    $photos = array_map(
        fn (int $index) => UploadedFile::fake()->createWithContent('photo-'.$index.'.webp', $webp),
        range(1, 5),
    );

    Livewire::actingAs($sender)->test(Chat::class, ['type' => 'direct', 'id' => ChatConversationUuid::from('direct', $connection->id)])
        ->set('photos', $photos)
        ->call('send')
        ->assertHasNoErrors();

    $message = ChatMessage::query()->where('connection_id', $connection->id)->firstOrFail();
    $attachments = $message->imageAttachments();
    expect($attachments)->toHaveCount(5);

    $this->actingAs($receiver)->get(route('chat.conversation', [
        'type' => 'direct',
        'id' => ChatConversationUuid::from('direct', $connection->id),
    ]))->assertOk()
        ->assertSee('+2', false)
        ->assertSee('data-image-index="3"', false)
        ->assertSee('data-chat-image-strip', false)
        ->assertSee('data-chat-image-position', false)
        ->assertSee('data-chat-image-stage', false)
        ->assertDontSee('data-chat-image-previous', false)
        ->assertDontSee('data-chat-image-next', false)
        ->assertSee('absolute left-4 top-4', false)
        ->assertSee('absolute right-4 top-4', false)
        ->assertSee('absolute bottom-3 left-1/2', false);

    $fifthImageUuid = ChatConversationUuid::from('attachment', $message->id, 4);
    $this->actingAs($receiver)->get(route('chat.attachment', $fifthImageUuid).'?download=1')
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="putraganteng-'.$fifthImageUuid.'.webp"');
});

it('purges expired direct messages and their photos when a participant opens the chat', function () {
    Storage::fake('local');
    Carbon::setTestNow('2026-11-25 12:00:00');
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $connection = ChatConnection::create([
        'user_one_id' => min($sender->id, $receiver->id),
        'user_two_id' => max($sender->id, $receiver->id),
        'requested_by' => $sender->id,
        'status' => 'accepted',
        'retention_days' => 2,
    ]);
    $path = 'chat-attachments/'.Str::uuid().'.webp';
    Storage::disk('local')->put($path, 'image');
    $expired = ChatMessage::create([
        'connection_id' => $connection->id,
        'sender_id' => $sender->id,
        'body' => json_encode(['__finwise_attachment' => 'images', 'items' => [$path], 'caption' => 'old']),
    ]);
    $expired->forceFill(['created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)])->save();
    $recent = ChatMessage::create(['connection_id' => $connection->id, 'sender_id' => $sender->id, 'body' => 'recent']);

    $this->actingAs($receiver)->get(route('chat.conversation', [
        'type' => 'direct',
        'id' => ChatConversationUuid::from('direct', $connection->id),
    ]))->assertOk()->assertSee('recent');

    expect(ChatMessage::find($expired->id))->toBeNull()
        ->and(ChatMessage::find($recent->id))->not->toBeNull();
    Storage::disk('local')->assertMissing($path);
    Carbon::setTestNow();
});

it('limits retention settings to group admins and supports staged message loading', function () {
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $group = ChatGroup::create(['name' => 'Keluarga', 'creator_id' => $admin->id]);
    $group->members()->attach($admin->id, ['role' => 'admin']);
    $group->members()->attach($member->id, ['role' => 'member']);
    foreach (range(1, 55) as $number) {
        ChatMessage::create(['group_id' => $group->id, 'sender_id' => $admin->id, 'body' => 'Pesan '.$number]);
    }

    Livewire::actingAs($member)->test(Chat::class, ['type' => 'group', 'id' => ChatConversationUuid::from('group', $group->id)])
        ->call('updateRetentionDays', 7)
        ->assertForbidden();

    Livewire::actingAs($admin)->test(Chat::class, ['type' => 'group', 'id' => ChatConversationUuid::from('group', $group->id)])
        ->assertViewHas('messages', fn ($messages) => $messages->count() === 50)
        ->assertViewHas('totalMessageCount', 55)
        ->call('updateRetentionDays', 7)
        ->assertHasNoErrors()
        ->call('loadOlderMessages')
        ->assertViewHas('messages', fn ($messages) => $messages->count() === 55);

    Livewire::actingAs($admin)->test(Chat::class, ['type' => 'group', 'id' => ChatConversationUuid::from('group', $group->id)])
        ->call('updateRetentionDays', 2)
        ->assertStatus(422);

    expect($group->fresh()->retention_days)->toBe(7);
});
