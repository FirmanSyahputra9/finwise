<?php

namespace App\Enums;

enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Income => __('ui.income'),
            self::Expense => __('ui.expense'),
            self::Transfer => __('ui.transfer'),
        };
    }
}
