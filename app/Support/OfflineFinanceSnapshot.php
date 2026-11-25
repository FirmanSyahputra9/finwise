<?php

namespace App\Support;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class OfflineFinanceSnapshot
{
    public static function forCurrentUser(): array
    {
        $now = now();
        $accounts = Account::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $budgets = Budget::with('category')->where('month', $now->month)->where('year', $now->year)->get();
        $transactions = Transaction::with(['category', 'account', 'toAccount'])
            ->latest('date')->latest('id')->limit(200)->get();
        $sum = fn (TransactionType $type) => (int) Transaction::ofType($type)
            ->inMonth($now->year, $now->month)->sum('amount');

        return [
            'userId' => auth()->id(),
            'locale' => app()->getLocale(),
            'capturedAt' => now()->toIso8601String(),
            'accounts' => $accounts->map(fn (Account $account) => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type->value,
                'opening_balance' => $account->opening_balance,
                'balance' => $account->balance,
            ])->values(),
            'categories' => $categories->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type->value,
            ])->values(),
            'transactions' => $transactions->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'type' => $transaction->type->value,
                'amount' => $transaction->amount,
                'date' => $transaction->date->toDateString(),
                'note' => $transaction->note,
                'account_id' => $transaction->account_id,
                'account' => $transaction->account?->name,
                'to_account_id' => $transaction->to_account_id,
                'to_account' => $transaction->toAccount?->name,
                'category_id' => $transaction->category_id,
                'category' => $transaction->category?->name,
            ])->values(),
            'budgets' => $budgets->map(fn (Budget $budget) => [
                'id' => $budget->id,
                'category_id' => $budget->category_id,
                'category' => $budget->category?->name,
                'amount' => $budget->amount,
                'spent' => $budget->spent(),
            ])->values(),
            'goals' => SavingsGoal::orderByRaw('target_date IS NULL')->orderBy('target_date')->latest('id')->get()
                ->map(fn (SavingsGoal $goal) => [
                    'id' => $goal->id,
                    'name' => $goal->name,
                    'target_amount' => $goal->target_amount,
                    'saved_amount' => $goal->saved_amount,
                    'target_date' => $goal->target_date?->toDateString(),
                ])->values(),
            'summary' => [
                'balance' => (int) $accounts->sum->balance,
                'income' => $sum(TransactionType::Income),
                'expense' => $sum(TransactionType::Expense),
                'monthly_budget' => (int) DB::table('monthly_budget_limits')
                    ->where('user_id', auth()->id())->where('month', $now->month)->where('year', $now->year)->value('amount'),
            ],
        ];
    }
}
