<?php

use App\Livewire\Dashboard;
use App\Models\Budget;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('shows how much of the monthly budget remains after expenses', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = Category::create(['name' => 'Makan', 'type' => 'expense']);
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    Budget::create(['category_id' => $category->id, 'amount' => 400_000, 'month' => now()->month, 'year' => now()->year]);
    Transaction::create(['account_id' => $account->id, 'category_id' => $category->id, 'type' => 'expense', 'amount' => 400_000, 'date' => today()]);
    DB::table('monthly_budget_limits')->insert([
        'user_id' => $user->id, 'month' => now()->month, 'year' => now()->year,
        'amount' => 1_000_000, 'created_at' => now(), 'updated_at' => now(),
    ]);

    Livewire::test(Dashboard::class)
        ->assertOk()
        ->assertSee('Sisa budget yang boleh dipakai')
        ->assertSee('Rp 600.000');
});

it('shows no unallocated amount before a monthly budget is configured', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Dashboard::class)
        ->assertOk()
        ->assertDontSee('Sisa budget yang boleh dipakai');
});
