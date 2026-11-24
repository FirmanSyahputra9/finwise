<?php

namespace App\Livewire\Concerns;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;

trait HasSummary
{
    /** Saldo total + pemasukan/pengeluaran bulan ini, dipakai header beberapa halaman. */
    protected function summary(): array
    {
        $now = now();
        $month = fn (TransactionType $type) => (int) Transaction::ofType($type)
            ->inMonth($now->year, $now->month)->sum('amount');

        return [
            'balance' => (int) Account::all()->sum->balance,
            'income' => $month(TransactionType::Income),
            'expense' => $month(TransactionType::Expense),
        ];
    }
}
