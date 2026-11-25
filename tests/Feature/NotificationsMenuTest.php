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
