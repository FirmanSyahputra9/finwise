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
        ->assertSee(__('ui.looks_good'))
        ->assertSee('Pemasukan & Pengeluaran')
        ->assertSee('Budget Bulan Ini');
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
        ->assertSee('Rp 30.000');
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
