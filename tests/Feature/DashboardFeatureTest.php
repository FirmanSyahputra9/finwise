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
        ->assertSee('IDR 600.000');
});

it('shows no unallocated amount before a monthly budget is configured', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Dashboard::class)
        ->assertOk()
        ->assertDontSee('Sisa budget yang boleh dipakai');
});

it('embeds an owner-scoped finance snapshot for offline use', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    Account::create(['name' => 'Dompet offline', 'type' => 'cash', 'opening_balance' => 25_000]);
    $other = User::factory()->create();
    $this->actingAs($other);
    Account::create(['name' => 'Rahasia orang lain', 'type' => 'cash']);
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('"name":"Dompet offline"', false)
        ->assertDontSee('"name":"Rahasia orang lain"', false)
        ->assertSee('"summary"', false);
});

it('provides five bottom navigation destinations and dashboard shortcuts for chat and categories', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-bottom-nav-item="0"', false)
        ->assertSee('data-bottom-nav-item="4"', false)
        ->assertSee('data-bottom-nav-route="dashboard"', false)
        ->assertSee('data-bottom-nav-route="profile"', false)
        ->assertDontSee('data-bottom-nav-item="5"', false)
        ->assertDontSee('data-bottom-nav-route="chat"', false)
        ->assertDontSee('data-bottom-nav-route="categories"', false)
        ->assertSee(route('chat'), false)
        ->assertSee(route('categories'), false);
});

it('shows the account avatar and balance visibility control in the dashboard header', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('profile'), false)
        ->assertSee('data-balance-toggle', false)
        ->assertSee('data-balance-value', false)
        ->assertSee('IDR 0')
        ->assertSee('data-hide-label="Sembunyikan saldo"', false)
        ->assertSee('data-show-label="Tampilkan saldo"', false);
});

it('renders the shared custom confirmation dialog in the app layout', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-confirm-modal', false)
        ->assertSee('role="alertdialog"', false)
        ->assertSee(__('ui.confirmation'))
        ->assertSee(__('ui.confirm'));
});
