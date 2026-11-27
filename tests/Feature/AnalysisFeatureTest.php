<?php

use App\Livewire\Analysis;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

it('renders the analysis page with daily chart buckets', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Analysis::class)
        ->assertOk()
        ->assertSee('data-summary-card', false)
        ->assertSee(__('ui.total_balance'))
        ->assertSee(__('ui.expense_status_safe'))
        ->assertSee(__('ui.average_income_expense'))
        ->assertSee('Budget Bulan Ini');
});

it('clarifies that category budgets are missing when the monthly limit is already set', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Analysis::class)
        ->set('monthlyBudget', 100_000)
        ->call('saveMonthlyBudget')
        ->assertSee(__('ui.no_category_budget'));
});

it('changes the summary spending guidance across the progress color ranges', function () {
    $this->actingAs(User::factory()->create());
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $incomeCategory = Category::create(['name' => 'Gaji', 'type' => 'income']);
    $expenseCategory = Category::create(['name' => 'Belanja', 'type' => 'expense']);
    Transaction::create(['account_id' => $account->id, 'category_id' => $incomeCategory->id, 'type' => 'income', 'amount' => 100_000, 'date' => today()]);

    foreach ([[10_000, 'expense_status_safe'], [30_000, 'expense_status_controlled'], [50_000, 'expense_status_attention'], [70_000, 'expense_status_high'], [99_999, 'expense_status_high'], [100_000, 'expense_status_limit'], [110_000, 'expense_status_exceeded']] as [$amount, $status]) {
        Transaction::where('type', 'expense')->delete();
        Transaction::create(['account_id' => $account->id, 'category_id' => $expenseCategory->id, 'type' => 'expense', 'amount' => $amount, 'date' => today()]);

        Livewire::test(Analysis::class)->assertSee(__('ui.'.$status));
    }
});

it('filters analysis totals by multiple selected categories', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $first = Category::create(['name' => 'Makan', 'type' => 'expense']);
    $second = Category::create(['name' => 'Transport', 'type' => 'expense']);
    $other = Category::create(['name' => 'Belanja', 'type' => 'expense']);

    foreach ([[$first, 10_000], [$second, 20_000], [$other, 40_000]] as [$category, $amount]) {
        Transaction::create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => $amount,
            'date' => today(),
        ]);
    }

    Livewire::test(Analysis::class)
        ->set('period', 'monthly')
        ->call('toggleAnalysisCategory', $first->id)
        ->call('toggleAnalysisCategory', $second->id)
        ->assertSet('analysisCategoryIds', [$first->id, $second->id])
        ->assertSee('IDR 30.000');
});

it('toggles a selected analysis category off', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $category = Category::create(['name' => 'Makan', 'type' => 'expense']);

    Livewire::test(Analysis::class)
        ->call('toggleAnalysisCategory', $category->id)
        ->call('toggleAnalysisCategory', $category->id)
        ->assertSet('analysisCategoryIds', []);
});

it('counts each daily chart bucket for one complete calendar date', function () {
    $this->actingAs(User::factory()->create());
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $category = Category::create(['name' => 'Makan', 'type' => 'expense']);
    Transaction::create(['account_id' => $account->id, 'category_id' => $category->id, 'type' => 'expense', 'amount' => 15_000, 'date' => today()]);
    Transaction::create(['account_id' => $account->id, 'category_id' => $category->id, 'type' => 'expense', 'amount' => 20_000, 'date' => today()->subDay()]);

    Livewire::test(Analysis::class)
        ->assertViewHas('rows', function ($rows) {
            return $rows->last()['expense'] === 15_000
                && $rows->slice(-2)->first()['expense'] === 20_000;
        });
});

it('shows the average income and expenses per selected chart bucket', function () {
    $this->actingAs(User::factory()->create());
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $incomeCategory = Category::create(['name' => 'Gaji', 'type' => 'income']);
    $expenseCategory = Category::create(['name' => 'Belanja', 'type' => 'expense']);
    Transaction::create(['account_id' => $account->id, 'category_id' => $incomeCategory->id, 'type' => 'income', 'amount' => 300_000, 'date' => today()]);
    Transaction::create(['account_id' => $account->id, 'category_id' => $incomeCategory->id, 'type' => 'income', 'amount' => 200_000, 'date' => today()->subDay()]);
    Transaction::create(['account_id' => $account->id, 'category_id' => $expenseCategory->id, 'type' => 'expense', 'amount' => 70_000, 'date' => today()]);

    Livewire::test(Analysis::class)
        ->assertViewHas('averageIncome', intdiv(500_000, 7))
        ->assertViewHas('averageExpense', intdiv(70_000, 7))
        ->assertSee(__('ui.average_income_period'))
        ->assertSee(__('ui.average_expense_period'))
        ->set('averageMode', 'active')
        ->assertViewHas('averageIncome', 250_000)
        ->assertViewHas('averageExpense', 70_000)
        ->assertSee(__('ui.average_income_period'));
});
