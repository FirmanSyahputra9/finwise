<?php

use App\Livewire\Profile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('builds the profile ID from registration time and same-second sequence', function () {
    $timestamp = Carbon::parse('2026-11-24 14:32:18');
    $first = User::factory()->create(['created_at' => $timestamp, 'updated_at' => $timestamp]);
    $second = User::factory()->create(['created_at' => $timestamp, 'updated_at' => $timestamp]);
    $third = User::factory()->create(['created_at' => $timestamp, 'updated_at' => $timestamp]);

    expect($first->profileId())->toBe('1832142411261')
        ->and($second->profileId())->toBe('1832142411262')
        ->and($third->profileId())->toBe('1832142411263');

    $this->actingAs($third);
    Livewire::test(Profile::class)->assertSee('ID akun: 1832142411263');
});

it('lets the user choose Indonesian, English, or Japanese for the current session', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    Livewire::test(Profile::class)
        ->set('language', 'ja')
        ->assertRedirect(route('profile'));

    expect(session('locale'))->toBe('ja');

    $this->withSession(['locale' => 'en'])
        ->get(route('dashboard'))
        ->assertOk();

    expect(app()->getLocale())->toBe('en');
});

it('shows selectable click effects, particle counts, and colors in profile appearance settings', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('profile'))
        ->assertOk()
        ->assertSee('data-sparkle-setting="type"', false)
        ->assertSee('data-screen-scroll', false)
        ->assertSee('data-screen-hero', false)
        ->assertSee('data-sparkle-value="heart"', false)
        ->assertSee('data-sparkle-value="flower"', false)
        ->assertSee('data-sparkle-setting="count"', false)
        ->assertSee('data-sparkle-value="16"', false)
        ->assertSee('data-sparkle-setting="color"', false)
        ->assertSee('data-sparkle-value="purple"', false)
        ->assertSee('Fuzzy Duck')
        ->assertSee('Rare Snake')
        ->assertSee('fuzzy-duck-40')
        ->assertSee('rare-snake-68')
        ->assertSee(asset('pattern/fuzzy-duck-40/style.css').'?v=2', false)
        ->assertSee(asset('pattern/rare-snake-68/style.css').'?v=2', false);
});

it('links the saved profile photo from the public storage disk in the avatar', function () {
    $user = User::factory()->create();
    $user->forceFill(['profile_photo_path' => 'profile-photos/'.$user->id.'/avatar.webp'])->save();

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee(Storage::disk('public')->url($user->profile_photo_path), false)
        ->assertSee('alt="Foto profil"', false);
});

it('renders built-in analysis labels in the selected language', function () {
    $this->actingAs(User::factory()->create());

    $this->withSession(['locale' => 'id'])
        ->get(route('analysis'))
        ->assertOk()
        ->assertSee('Analisis')
        ->assertSee('Budget Bulan Ini');

    $this->withSession(['locale' => 'en'])
        ->get(route('analysis'))
        ->assertOk()
        ->assertSee('Analysis')
        ->assertSee('This Month’s Budget');

    $this->withSession(['locale' => 'ja'])
        ->get(route('analysis'))
        ->assertOk()
        ->assertSee('分析')
        ->assertSee('今月の予算');
});

it('starts the registration sequence again in a different second', function () {
    $timestamp = Carbon::parse('2026-11-24 14:32:18');
    User::factory()->create(['created_at' => $timestamp, 'updated_at' => $timestamp]);
    $later = User::factory()->create([
        'created_at' => $timestamp->copy()->addSecond(),
        'updated_at' => $timestamp->copy()->addSecond(),
    ]);

    expect($later->profileId())->toBe('1932142411261');
});
