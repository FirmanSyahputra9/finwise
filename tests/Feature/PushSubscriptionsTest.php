<?php

use App\Livewire\NotificationsMenu;
use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\FinanceActivityNotification;
use App\Support\WebPushSender;
use App\Support\WebPushSettings;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function validPushSubscription(string $endpoint = 'https://fcm.googleapis.com/fcm/send/example'): array
{
    return [
        'endpoint' => $endpoint,
        'keys' => [
            'p256dh' => 'browser-public-key',
            'auth' => 'browser-auth-token',
        ],
    ];
}

it('stores encrypted push subscriptions for the signed-in user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->postJson(route('notifications.push-subscription.store'), validPushSubscription())
        ->assertOk()
        ->assertJson(['subscribed' => true]);

    $subscription = PushSubscription::query()->firstOrFail();
    $stored = DB::table('push_subscriptions')->where('id', $subscription->id)->first();

    expect($subscription->user_id)->toBe($user->id)
        ->and($subscription->endpoint)->toBe('https://fcm.googleapis.com/fcm/send/example')
        ->and($subscription->public_key)->toBe('browser-public-key')
        ->and($stored->endpoint)->not->toBe('https://fcm.googleapis.com/fcm/send/example')
        ->and($stored->auth_token)->not->toBe('browser-auth-token');
});

it('rejects push endpoints outside known browser push services', function () {
    $this->actingAs(User::factory()->create())
        ->postJson(route('notifications.push-subscription.store'), validPushSubscription('https://localhost/private'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('endpoint');

    expect(PushSubscription::query()->count())->toBe(0);
});

it('allows a user to remove only their own browser subscription', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $endpoint = validPushSubscription()['endpoint'];
    $this->actingAs($owner)->postJson(route('notifications.push-subscription.store'), validPushSubscription())->assertOk();

    $this->actingAs($otherUser)
        ->deleteJson(route('notifications.push-subscription.destroy'), ['endpoint' => $endpoint])
        ->assertOk();

    expect(PushSubscription::query()->count())->toBe(1);
});

it('hides the push permission control when server keys are not configured', function () {
    $settings = Mockery::mock(WebPushSettings::class);
    $settings->shouldReceive('publicKey')->andReturn(null);
    app()->instance(WebPushSettings::class, $settings);
    $this->actingAs(User::factory()->create());

    Livewire::test(NotificationsMenu::class)
        ->assertDontSee('data-enable-notifications', false);
});

it('renders a first-open notification permission prompt when push is configured', function () {
    $settings = Mockery::mock(WebPushSettings::class);
    $settings->shouldReceive('publicKey')->andReturn('public-vapid-key');
    app()->instance(WebPushSettings::class, $settings);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-push-first-prompt', false)
        ->assertSee(__('ui.push_prompt_allow'));
});

it('sends a web push channel notification after storing the database notification', function () {
    $user = User::factory()->create();
    $sender = Mockery::mock(WebPushSender::class);
    $sender->shouldReceive('send')->once()->with($user, ['title' => 'Saved', 'message' => 'Private details']);
    app()->instance(WebPushSender::class, $sender);

    $user->notify(new FinanceActivityNotification(['title' => 'Saved', 'message' => 'Private details']));

    expect($user->notifications()->count())->toBe(1);
});
