<?php

namespace App\Enums;

enum AccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case EWallet = 'ewallet';

    public function label(): string
    {
        return match ($this) {
            self::Cash => __('ui.cash'),
            self::Bank => __('ui.bank'),
            self::EWallet => __('ui.ewallet'),
        };
    }
}
