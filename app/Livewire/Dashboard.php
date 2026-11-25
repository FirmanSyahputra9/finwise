<?php

namespace App\Livewire;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        $now = now();

        $month = fn (TransactionType $type) => (int) Transaction::ofType($type)
            ->inMonth($now->year, $now->month)
            ->sum('amount');

        $accounts = Account::all();
        $offlineDataJson = json_encode([
            'userId' => auth()->id(),
            'locale' => app()->getLocale(),
            'accounts' => $accounts->map(fn (Account $account) => ['id' => $account->id, 'name' => $account->name])->values(),
            'categories' => Category::orderBy('name')->get()->map(fn (Category $category) => ['id' => $category->id, 'name' => $category->name, 'type' => $category->type->value])->values(),
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        return view('livewire.dashboard', [
            'totalBalance' => $accounts->sum->balance,
            'income' => $month(TransactionType::Income),
            'expense' => $month(TransactionType::Expense),
            'accounts' => $accounts,
            'offlineCategories' => Category::orderBy('name')->get(),
            'offlineDataJson' => $offlineDataJson,
            'recent' => Transaction::with(['category', 'account'])->latest('date')->latest('id')->limit(5)->get(),
            'budgets' => Budget::with('category')->where('month', $now->month)->where('year', $now->year)->get(),
            'monthlyBudget' => (int) \Illuminate\Support\Facades\DB::table('monthly_budget_limits')->where('user_id', auth()->id())->where('month', $now->month)->where('year', $now->year)->value('amount'),
            'allocatedBudget' => (int) Budget::where('month', $now->month)->where('year', $now->year)->sum('amount'),
        ]);
    }
}
