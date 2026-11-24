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

it('starts the registration sequence again in a different second', function () {
    $timestamp = Carbon::parse('2026-11-24 14:32:18');
    User::factory()->create(['created_at' => $timestamp, 'updated_at' => $timestamp]);
    $later = User::factory()->create([
        'created_at' => $timestamp->copy()->addSecond(),
        'updated_at' => $timestamp->copy()->addSecond(),
    ]);

    expect($later->profileId())->toBe('1932241120261');
});
