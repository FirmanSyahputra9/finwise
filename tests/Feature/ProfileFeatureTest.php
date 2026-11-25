<?php

use App\Livewire\Profile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

it('builds the profile ID from registration time and same-second sequence', function () {
    $timestamp = Carbon::parse('2026-11-24 14:32:18');
    $first = User::factory()->create(['created_at' => $timestamp, 'updated_at' => $timestamp]);
    $second = User::factory()->create(['created_at' => $timestamp, 'updated_at' => $timestamp]);
    $third = User::factory()->create(['created_at' => $timestamp, 'updated_at' => $timestamp]);

    expect($first->profileId())->toBe('1832241120261')
        ->and($second->profileId())->toBe('1832241120262')
        ->and($third->profileId())->toBe('1832241120263');

    $this->actingAs($third);
    Livewire::test(Profile::class)->assertSee('ID: 1832241120263');
});

it('lets the user choose Indonesian, English, or Japanese for the current session', function () {
    $user = \App\Models\User::factory()->create();

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

    expect($later->profileId())->toBe('1932241120261');
});
