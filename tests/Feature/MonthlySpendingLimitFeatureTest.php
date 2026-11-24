<?php

use App\Livewire\Transactions\Index as Transactions;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('allows monthly expenses up to the budget even when no category budgets are allocated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $category = Category::create(['name' => 'Belanja', 'type' => 'expense']);
    DB::table('monthly_budget_limits')->insert([
        'user_id' => $user->id, 'month' => now()->month, 'year' => now()->year,
        'amount' => 1_000_000, 'created_at' => now(), 'updated_at' => now(),
    ]);

    Livewire::test(Transactions::class)
        ->set('account_id', $account->id)
        ->set('category_id', $category->id)
        ->set('amount', 1_000_000)
        ->set('date', today()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    expect(Transaction::count())->toBe(1)
        ->and(Transaction::sum('amount'))->toBe(1_000_000);
});

it('blocks monthly expense totals above the budget ceiling', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $category = Category::create(['name' => 'Belanja', 'type' => 'expense']);
    DB::table('monthly_budget_limits')->insert([
        'user_id' => $user->id, 'month' => now()->month, 'year' => now()->year,
        'amount' => 100_000, 'created_at' => now(), 'updated_at' => now(),
    ]);
    Transaction::create(['account_id' => $account->id, 'category_id' => $category->id, 'type' => 'expense', 'amount' => 80_000, 'date' => today()]);

    Livewire::test(Transactions::class)
        ->set('account_id', $account->id)
        ->set('category_id', $category->id)
        ->set('amount', 30_000)
        ->set('date', today()->toDateString())
        ->call('save')
        ->assertHasErrors(['amount']);

    expect(Transaction::count())->toBe(1);
});

it('checks the full monthly ceiling when an existing expense is edited', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $category = Category::create(['name' => 'Belanja', 'type' => 'expense']);
    DB::table('monthly_budget_limits')->insert([
        'user_id' => $user->id, 'month' => now()->month, 'year' => now()->year,
        'amount' => 100_000, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $transaction = Transaction::create(['account_id' => $account->id, 'category_id' => $category->id, 'type' => 'expense', 'amount' => 80_000, 'date' => today()]);

    Livewire::test(Transactions::class)
        ->call('edit', $transaction->id)
        ->set('amount', 110_000)
        ->call('save')
        ->assertHasErrors(['amount']);

    expect($transaction->fresh()->amount)->toBe(80_000);
});
