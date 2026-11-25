<?php

use App\Livewire\Categories;
use App\Livewire\AccountManager;
use App\Livewire\Analysis;
use App\Livewire\DeletedItems;
use App\Livewire\DeletionUndo;
use App\Livewire\SavingsGoals;
use App\Livewire\Transactions\Index as Transactions;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\SavingsGoal;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('renders the empty undo components with stable Livewire roots', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(DeletionUndo::class)->assertDontSee('Dihapus sementara');
    Livewire::test(DeletedItems::class)->assertDontSee('Baru dihapus');
});

it('soft deletes a transaction immediately and lets the owner undo it from the deleted list', function () {
    $this->actingAs(User::factory()->create());
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $category = Category::create(['name' => 'Makan', 'type' => 'expense']);
    $transaction = Transaction::create([
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 25_000,
        'date' => today(),
    ]);

    Livewire::test(Transactions::class)
        ->call('delete', $transaction->id)
        ->assertDispatched('deletion-made', type: 'transaction', id: $transaction->id);

    expect(Transaction::find($transaction->id))->toBeNull()
        ->and(Transaction::withTrashed()->findOrFail($transaction->id)->trashed())->toBeTrue();

    Livewire::test(DeletedItems::class)
        ->assertSee('Transaksi: Pengeluaran')
        ->call('undo', 'transaction', $transaction->id)
        ->assertDispatched('deletion-undone');

    expect(Transaction::findOrFail($transaction->id)->trashed())->toBeFalse();
});

it('permanently deletes a trashed transaction only for its owner', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $this->actingAs($owner);
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $transaction = Transaction::create(['account_id' => $account->id, 'type' => 'expense', 'amount' => 10_000, 'date' => today()]);
    $transaction->delete();

    Livewire::actingAs($other)
        ->test(DeletedItems::class)
        ->call('permanentlyDelete', 'transaction', $transaction->id)
        ->assertNotFound();
    expect(DB::table('transactions')->where('id', $transaction->id)->exists())->toBeTrue();

    Livewire::actingAs($owner)
        ->test(DeletedItems::class)
        ->call('permanentlyDelete', 'transaction', $transaction->id)
        ->assertHasNoErrors();
    expect(DB::table('transactions')->where('id', $transaction->id)->exists())->toBeFalse();
});

it('permanently deletes all trashed finance records for only the current owner', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($owner);
    $ownerAccount = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $ownerTransaction = Transaction::create(['account_id' => $ownerAccount->id, 'type' => 'expense', 'amount' => 10_000, 'date' => today()]);
    $ownerTransaction->delete();
    $ownerAccount->delete();

    $this->actingAs($other);
    $otherAccount = Account::create(['name' => 'Bank', 'type' => 'cash']);
    $otherTransaction = Transaction::create(['account_id' => $otherAccount->id, 'type' => 'expense', 'amount' => 20_000, 'date' => today()]);
    $otherTransaction->delete();

    Livewire::actingAs($owner)
        ->test(DeletedItems::class)
        ->call('permanentlyDeleteAll')
        ->assertHasNoErrors();

    expect(DB::table('transactions')->where('id', $ownerTransaction->id)->exists())->toBeFalse()
        ->and(DB::table('accounts')->where('id', $ownerAccount->id)->exists())->toBeFalse()
        ->and(DB::table('transactions')->where('id', $otherTransaction->id)->exists())->toBeTrue()
        ->and(DB::table('accounts')->where('id', $otherAccount->id)->exists())->toBeTrue();
});

it('treats a repeated fast delete request as a harmless no-op', function () {
    $this->actingAs(User::factory()->create());
    $account = Account::create(['name' => 'Dompet', 'type' => 'cash']);
    $spareAccount = Account::create(['name' => 'Cadangan', 'type' => 'cash']);
    $category = Category::create(['name' => 'Makan', 'type' => 'expense']);
    $deletedCategory = Category::create(['name' => 'Belanja', 'type' => 'expense']);
    $budgetCategory = Category::create(['name' => 'Transportasi', 'type' => 'expense']);
    $transaction = Transaction::create([
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 10_000,
        'date' => today(),
    ]);
    $budget = Budget::create([
        'category_id' => $budgetCategory->id,
        'amount' => 100_000,
        'month' => now()->month,
        'year' => now()->year,
    ]);
    $goal = SavingsGoal::create(['name' => 'Liburan', 'target_amount' => 1_000_000]);

    Livewire::test(Transactions::class)
        ->call('delete', $transaction->id)
        ->call('delete', $transaction->id)
        ->assertHasNoErrors();
    Livewire::test(AccountManager::class)
        ->call('delete', $spareAccount->id)
        ->call('delete', $spareAccount->id)
        ->assertHasNoErrors();
    Livewire::test(Categories::class)
        ->call('delete', $deletedCategory->id)
        ->call('delete', $deletedCategory->id)
        ->assertHasNoErrors();
    Livewire::test(Analysis::class)
        ->call('deleteBudget', $budget->id)
        ->call('deleteBudget', $budget->id)
        ->assertHasNoErrors();
    Livewire::test(SavingsGoals::class)
        ->call('delete', $goal->id)
        ->call('delete', $goal->id)
        ->assertHasNoErrors();

    expect(Transaction::withTrashed()->findOrFail($transaction->id)->trashed())->toBeTrue()
        ->and(Account::withTrashed()->findOrFail($spareAccount->id)->trashed())->toBeTrue()
        ->and(Category::withTrashed()->findOrFail($deletedCategory->id)->trashed())->toBeTrue()
        ->and(Budget::withTrashed()->findOrFail($budget->id)->trashed())->toBeTrue()
        ->and(SavingsGoal::withTrashed()->findOrFail($goal->id)->trashed())->toBeTrue();
});

it('soft deletes category budgets with the category and restores them together', function () {
    $this->actingAs(User::factory()->create());
    $category = Category::create(['name' => 'Belanja', 'type' => 'expense']);
    $budget = Budget::create([
        'category_id' => $category->id,
        'amount' => 500_000,
        'month' => now()->month,
        'year' => now()->year,
    ]);

    Livewire::test(Categories::class)
        ->call('delete', $category->id)
        ->assertDispatched('deletion-made', type: 'category', id: $category->id);

    expect(Category::find($category->id))->toBeNull()
        ->and(Budget::find($budget->id))->toBeNull()
        ->and(Budget::withTrashed()->findOrFail($budget->id)->trashed())->toBeTrue();

    Livewire::test(DeletedItems::class)->call('undo', 'category', $category->id);

    expect(Category::find($category->id))->not->toBeNull()
        ->and(Budget::find($budget->id))->not->toBeNull();
});

it('permanently deletes expired rows only when their owner makes another request', function () {
    $ownerA = User::factory()->create();
    $ownerB = User::factory()->create();

    $this->actingAs($ownerA);
    $accountA = Account::create(['name' => 'A', 'type' => 'cash']);
    $transactionA = Transaction::create(['account_id' => $accountA->id, 'type' => 'expense', 'amount' => 10, 'date' => today()]);
    $transactionA->delete();

    $this->actingAs($ownerB);
    $accountB = Account::create(['name' => 'B', 'type' => 'cash']);
    $transactionB = Transaction::create(['account_id' => $accountB->id, 'type' => 'expense', 'amount' => 20, 'date' => today()]);
    $transactionB->delete();

    $this->travel(4)->days();
    $this->actingAs($ownerB)->get(route('dashboard'))->assertOk();

    expect(DB::table('transactions')->where('id', $transactionB->id)->exists())->toBeFalse()
        ->and(DB::table('transactions')->where('id', $transactionA->id)->exists())->toBeTrue();

    $this->actingAs($ownerA)->get(route('dashboard'))->assertOk();

    expect(DB::table('transactions')->where('id', $transactionA->id)->exists())->toBeFalse();
});
