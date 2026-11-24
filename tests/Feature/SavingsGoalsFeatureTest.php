<?php

use App\Livewire\SavingsGoals;
use App\Models\SavingsGoal;
use App\Models\User;
use Livewire\Livewire;

it('creates a savings goal and validates its amount', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(SavingsGoals::class)
        ->set('name', 'Dana darurat')
        ->set('targetAmount', '5000000')
        ->set('targetDate', today()->addMonth()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    expect(SavingsGoal::firstOrFail())
        ->name->toBe('Dana darurat')
        ->target_amount->toBe(5_000_000);

    Livewire::test(SavingsGoals::class)
        ->set('name', 'Target tidak valid')
        ->set('targetAmount', '0')
        ->call('save')
        ->assertHasErrors(['targetAmount']);
});

it('edits a savings goal and adds contributions', function () {
    $this->actingAs(User::factory()->create());
    $goal = SavingsGoal::create(['name' => 'Liburan', 'target_amount' => 2_000_000, 'saved_amount' => 100_000]);
    $component = Livewire::test(SavingsGoals::class)
        ->call('edit', $goal->id)
        ->assertSet('editingId', $goal->id)
        ->set('name', 'Liburan keluarga')
        ->set('targetAmount', '3000000')
        ->set('targetDate', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($goal->fresh()->name)->toBe('Liburan keluarga')
        ->and($goal->fresh()->target_amount)->toBe(3_000_000);

    $component->set('contribution', '250000')
        ->call('addContribution', $goal->id)
        ->assertHasNoErrors();
    expect($goal->fresh()->saved_amount)->toBe(350_000);
});

it('deletes a savings goal', function () {
    $this->actingAs(User::factory()->create());
    $goal = SavingsGoal::create(['name' => 'Laptop', 'target_amount' => 10_000_000]);

    Livewire::test(SavingsGoals::class)->call('delete', $goal->id);

    expect(SavingsGoal::find($goal->id))->toBeNull();
});
