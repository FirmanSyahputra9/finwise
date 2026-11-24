<?php

namespace App\Support;

class Money
{
    public static function rp(int|float $amount): string
    {
        return ($amount < 0 ? '-' : '').'Rp '.number_format(abs($amount), 0, ',', '.');
    }
}
