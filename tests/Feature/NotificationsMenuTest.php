<?php

use App\Livewire\NotificationsMenu;
use App\Models\DatabaseNotification;
use App\Models\User;
use App\Notifications\FinanceActivityNotification;
use Livewire\Livewire;

it('offers a phone notification permission control', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(NotificationsMenu::class)
        ->assertSee('data-enable-notifications', false)
        ->assertSee(__('ui.enable_notifications'));
});

it('formats notification time in the application timezone before rendering', function () {
    $user = User::factory()->create();
    $user->notify(new FinanceActivityNotification(['title' => 'Satu']));
    $this->actingAs($user);
    config(['app.display_timezone' => 'Asia/Jakarta']);

    $notification = $user->notifications()->first();
    $notification->forceFill(['created_at' => now('UTC')->setTime(5, 7)])->save();

    Livewire::test(NotificationsMenu::class)
        ->assertSee('12:07')
        ->assertSee('datetime="'.$notification->created_at->toIso8601String().'"', false);
});

it('moves all of the current user notifications to soft delete', function () {
    $user = User::factory()->create();
    $user->notify(new FinanceActivityNotification(['title' => 'Satu']));
    $user->notify(new FinanceActivityNotification(['title' => 'Dua']));
    $this->actingAs($user);

    Livewire::test(NotificationsMenu::class)
        ->call('deleteAll')
        ->assertHasNoErrors();

    expect($user->notifications()->count())->toBe(0)
        ->and(DatabaseNotification::onlyTrashed()->where('notifiable_id', $user->id)->count())->toBe(2);
});
