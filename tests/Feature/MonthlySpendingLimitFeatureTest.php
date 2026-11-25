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

it('allows monthly expenses to exceed the budget ceiling', function () {
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
        ->set('amount', 130_000)
        ->set('date', today()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    expect(Transaction::count())->toBe(2)
        ->and(Transaction::sum('amount'))->toBe(210_000);
});

it('allows an edited expense to exceed the monthly budget ceiling', function () {
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
        ->assertHasNoErrors();

    expect($transaction->fresh()->amount)->toBe(110_000);
});

it('syncs offline expenses even when their total exceeds the monthly ceiling', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $category = Category::create(['name' => 'Belanja', 'type' => 'expense']);
    DB::table('monthly_budget_limits')->insert([
        'user_id' => $user->id, 'month' => now()->month, 'year' => now()->year,
        'amount' => 100_000, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $firstId = (string) str()->uuid();
    $secondId = (string) str()->uuid();

    $response = $this->postJson(route('transactions.sync'), [
        'owner_id' => $user->id,
        'transactions' => [
            ['client_id' => $firstId, 'type' => 'expense', 'amount' => '70000', 'date' => today()->toDateString(), 'account_id' => $account->id, 'category_id' => $category->id],
            ['client_id' => $secondId, 'type' => 'expense', 'amount' => '40000', 'date' => today()->toDateString(), 'account_id' => $account->id, 'category_id' => $category->id],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('synced.0', $firstId)
        ->assertJsonPath('synced.1', $secondId)
        ->assertJsonPath('failed', []);
    expect(Transaction::sum('amount'))->toBe(110_000);
});
