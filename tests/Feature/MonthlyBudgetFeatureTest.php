<?php

use App\Livewire\Analysis;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('saves and updates the monthly budget ceiling', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Analysis::class)
        ->set('monthlyBudget', 1_500_000)
        ->call('saveMonthlyBudget')
        ->assertHasNoErrors()
        ->assertSet('monthlyBudget', 1_500_000);

    expect(DB::table('monthly_budget_limits')->where('user_id', $user->id)->value('amount'))->toBe(1_500_000);
});

it('requires a monthly ceiling before saving category allocations', function () {
    $this->actingAs(User::factory()->create());
    $category = Category::create(['name' => 'Makan', 'type' => 'expense']);

    Livewire::test(Analysis::class)
        ->set('categoryId', $category->id)
        ->set('budgetAmount', 100_000)
        ->call('saveBudget')
        ->assertHasErrors(['budgetAmount']);

    expect(Budget::count())->toBe(0);
});

it('prevents category allocations from exceeding the monthly ceiling', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $first = Category::create(['name' => 'Makan', 'type' => 'expense']);
    $second = Category::create(['name' => 'Transport', 'type' => 'expense']);
    DB::table('monthly_budget_limits')->insert([
        'user_id' => $user->id, 'month' => now()->month, 'year' => now()->year,
        'amount' => 1_000_000, 'created_at' => now(), 'updated_at' => now(),
    ]);
    Budget::create(['category_id' => $first->id, 'amount' => 800_000, 'month' => now()->month, 'year' => now()->year]);

    Livewire::test(Analysis::class)
        ->set('categoryId', $second->id)
        ->set('budgetAmount', 300_000)
        ->call('saveBudget')
        ->assertHasErrors(['budgetAmount']);

    expect(Budget::count())->toBe(1);
});

it('creates, edits, and deletes a category allocation within the ceiling', function () {
    $this->actingAs(User::factory()->create());
    $category = Category::create(['name' => 'Makan', 'type' => 'expense']);
    DB::table('monthly_budget_limits')->insert([
        'user_id' => auth()->id(), 'month' => now()->month, 'year' => now()->year,
        'amount' => 1_000_000, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $component = Livewire::test(Analysis::class)
        ->set('categoryId', $category->id)
        ->set('budgetAmount', 400_000)
        ->call('saveBudget')
        ->assertHasNoErrors();
    $budget = Budget::firstOrFail();
    expect($budget->amount)->toBe(400_000);

    $component->call('editBudget', $budget->id)
        ->assertSet('budgetAmount', 400_000)
        ->set('budgetAmount', 600_000)
        ->call('saveBudget')
        ->assertHasNoErrors();
    expect($budget->fresh()->amount)->toBe(600_000);

    $component->call('deleteBudget', $budget->id);
    expect(Budget::count())->toBe(0);
});

it('rejects invalid monthly budget values', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Analysis::class)
        ->set('monthlyBudget', 0)
        ->call('saveMonthlyBudget')
        ->assertHasErrors(['monthlyBudget']);
});
