<?php

use App\Livewire\Transactions\Index as Transactions;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Livewire;

it('defaults the transaction filter end date to today on mount', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Transactions::class)
        ->assertSet('dateTo', today()->toDateString());

    Livewire::test(Transactions::class)
        ->set('dateTo', today()->subDay()->toDateString())
        ->call('resetFilters')
        ->assertSet('dateTo', today()->toDateString());
});

it('labels today and yesterday dates in the transaction list', function () {
    $this->actingAs(User::factory()->create());
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $category = Category::create(['name' => 'Makan', 'type' => 'expense']);

    foreach ([today(), today()->subDay()] as $date) {
        Transaction::create([
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 10_000,
            'date' => $date,
        ]);
    }

    Livewire::test(Transactions::class)
        ->set('dateFrom', today()->subDay()->toDateString())
        ->assertSee(__('ui.today'))
        ->assertSee(__('ui.yesterday'));
});

it('creates a categorized expense with an integer amount', function () {
    $this->actingAs(User::factory()->create());
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $category = Category::create(['name' => 'Makan', 'type' => 'expense']);

    Livewire::test(Transactions::class)
        ->set('account_id', $account->id)
        ->set('category_id', $category->id)
        ->set('amount', 125_000)
        ->set('date', today()->toDateString())
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('amount', null);

    expect(Transaction::firstOrFail()->amount)->toBe(125_000);
});

it('preserves large nominal values without JavaScript number rounding', function () {
    $this->actingAs(User::factory()->create());
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $category = Category::create(['name' => 'Belanja', 'type' => 'expense']);
    $amount = '9007199254740';

    Livewire::test(Transactions::class)
        ->set('account_id', $account->id)
        ->set('category_id', $category->id)
        ->set('amount', $amount)
        ->set('date', today()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    expect((string) Transaction::firstOrFail()->amount)->toBe($amount);
});

it('rejects a transaction amount longer than 13 digits', function () {
    $this->actingAs(User::factory()->create());
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $category = Category::create(['name' => 'Belanja', 'type' => 'expense']);

    Livewire::test(Transactions::class)
        ->set('account_id', $account->id)
        ->set('category_id', $category->id)
        ->set('amount', '10000000000000')
        ->set('date', today()->toDateString())
        ->call('save')
        ->assertHasErrors(['amount']);

    expect(Transaction::count())->toBe(0);
});

it('defers formatted amount input updates until the form action', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('transactions'))
        ->assertOk()
        ->assertSee('$wire.set(this.model, digits || null, false)', false)
        ->assertSee('beforeInput($event)', false)
        ->assertSee('beforePaste($event)', false)
        ->assertSee('/[^\\d.]/', false)
        ->assertSee('existingDigits - selectedDigits + insertedDigits > 13', false)
        ->assertSee("nextDigits.startsWith('00')", false)
        ->assertSee('input.value = this.display', false)
        ->assertSee('digits.length > 13', false)
        ->assertSee("digits.replace(/^0{2,}/, '0')", false);
});

it('rejects a category that belongs to another transaction type', function () {
    $this->actingAs(User::factory()->create());
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $incomeCategory = Category::create(['name' => 'Gaji', 'type' => 'income']);

    Livewire::test(Transactions::class)
        ->set('account_id', $account->id)
        ->set('category_id', $incomeCategory->id)
        ->set('amount', 50_000)
        ->set('date', today()->toDateString())
        ->call('save')
        ->assertHasErrors(['category_id']);

    expect(Transaction::count())->toBe(0);
});

it('creates a transfer between different accounts', function () {
    $this->actingAs(User::factory()->create());
    $source = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $destination = Account::create(['name' => 'Bank', 'type' => 'bank']);

    Livewire::test(Transactions::class)
        ->set('type', 'transfer')
        ->set('account_id', $source->id)
        ->set('to_account_id', $destination->id)
        ->set('amount', 200_000)
        ->set('date', today()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    expect(Transaction::firstOrFail()->to_account_id)->toBe($destination->id);
});

it('edits and deletes a transaction', function () {
    $this->actingAs(User::factory()->create());
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $category = Category::create(['name' => 'Makan', 'type' => 'expense']);
    $transaction = Transaction::create(['account_id' => $account->id, 'category_id' => $category->id, 'type' => 'expense', 'amount' => 10_000, 'date' => today()]);

    $component = Livewire::test(Transactions::class)
        ->call('edit', $transaction->id)
        ->assertSet('editingId', $transaction->id)
        ->set('amount', 15_000)
        ->call('save')
        ->assertHasNoErrors();

    expect($transaction->fresh()->amount)->toBe(15_000);
    $component->call('delete', $transaction->id);
    expect(Transaction::find($transaction->id))->toBeNull();
});

it('only offers filter categories, accounts, and types present in transactions', function () {
    $this->actingAs(User::factory()->create());
    $usedAccount = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $unusedAccount = Account::create(['name' => 'Bank kosong', 'type' => 'bank']);
    $usedCategory = Category::create(['name' => 'Makan', 'type' => 'expense']);
    Category::create(['name' => 'Belum dipakai', 'type' => 'expense']);
    Transaction::create([
        'account_id' => $usedAccount->id,
        'category_id' => $usedCategory->id,
        'type' => 'expense',
        'amount' => 25_000,
        'date' => today(),
    ]);

    Livewire::test(Transactions::class)
        ->assertViewHas('filterCategories', fn ($categories) => $categories->pluck('id')->all() === [$usedCategory->id])
        ->assertViewHas('filterAccounts', fn ($accounts) => $accounts->pluck('id')->all() === [$usedAccount->id])
        ->assertViewHas('filterTypes', fn ($types) => $types === ['expense']);
});

it('limits the category filter to categories matching the selected transaction type', function () {
    $this->actingAs(User::factory()->create());
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $expenseCategory = Category::create(['name' => 'Makan', 'type' => 'expense']);
    $incomeCategory = Category::create(['name' => 'Gaji', 'type' => 'income']);
    Transaction::create(['account_id' => $account->id, 'category_id' => $expenseCategory->id, 'type' => 'expense', 'amount' => 10_000, 'date' => today()]);
    Transaction::create(['account_id' => $account->id, 'category_id' => $incomeCategory->id, 'type' => 'income', 'amount' => 50_000, 'date' => today()]);

    Livewire::test(Transactions::class)
        ->set('filterCategory', $incomeCategory->id)
        ->set('filterType', 'expense')
        ->assertSet('filterCategory', '')
        ->assertViewHas('filterCategories', fn ($categories) => $categories->pluck('id')->all() === [$expenseCategory->id])
        ->set('filterType', 'income')
        ->assertViewHas('filterCategories', fn ($categories) => $categories->pluck('id')->all() === [$incomeCategory->id])
        ->set('filterType', 'transfer')
        ->assertViewHas('filterCategories', fn ($categories) => $categories->isEmpty());
});
