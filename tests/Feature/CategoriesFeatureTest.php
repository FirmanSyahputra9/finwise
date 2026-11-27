<?php

use App\Livewire\Categories;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

it('defaults the category transaction filter end date to today on mount', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Categories::class)
        ->assertSet('dateTo', today()->toDateString());
});

it('creates a category and rejects an empty name', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Categories::class)
        ->set('name', 'Hiburan')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('name', '');

    expect(Category::where('name', 'Hiburan')->where('type', 'expense')->exists())->toBeTrue();

    Livewire::test(Categories::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('shows category transactions and applies type and date filters', function () {
    $this->actingAs(User::factory()->create());
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $category = Category::create(['name' => 'Belanja', 'type' => 'expense']);
    $otherCategory = Category::create(['name' => 'Makan', 'type' => 'expense']);

    Transaction::create(['account_id' => $account->id, 'category_id' => $category->id, 'type' => 'expense', 'amount' => 20_000, 'date' => today()]);
    Transaction::create(['account_id' => $account->id, 'category_id' => $category->id, 'type' => 'expense', 'amount' => 30_000, 'date' => today()->subDay()]);
    Transaction::create(['account_id' => $account->id, 'category_id' => $otherCategory->id, 'type' => 'expense', 'amount' => 90_000, 'date' => today()]);

    Livewire::test(Categories::class)
        ->set('selectedCategory', $category->id)
        ->set('dateFrom', today()->toDateString())
        ->set('typeFilter', 'expense')
        ->assertSee('IDR 20.000')
        ->assertDontSee('IDR 30.000')
        ->assertDontSee('IDR 90.000');
});

it('deletes a category', function () {
    $this->actingAs(User::factory()->create());
    $category = Category::create(['name' => 'Lainnya', 'type' => 'expense']);

    Livewire::test(Categories::class)->call('delete', $category->id);

    expect(Category::find($category->id))->toBeNull();
});
