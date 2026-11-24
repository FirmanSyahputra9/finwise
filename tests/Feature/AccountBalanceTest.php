<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;

it('menghitung saldo dari pemasukan, pengeluaran, dan transfer', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cash = Account::create(['name' => 'Dompet', 'type' => 'cash', 'opening_balance' => 100_000]);
    $bank = Account::create(['name' => 'BCA', 'type' => 'bank', 'opening_balance' => 0]);
    $gaji = Category::create(['name' => 'Gaji', 'type' => 'income']);
    $makan = Category::create(['name' => 'Makan', 'type' => 'expense']);

    Transaction::create(['account_id' => $cash->id, 'category_id' => $gaji->id, 'type' => 'income', 'amount' => 500_000, 'date' => today()]);
    Transaction::create(['account_id' => $cash->id, 'category_id' => $makan->id, 'type' => 'expense', 'amount' => 50_000, 'date' => today()]);
    Transaction::create(['account_id' => $cash->id, 'to_account_id' => $bank->id, 'type' => 'transfer', 'amount' => 200_000, 'date' => today()]);

    expect($cash->balance)->toBe(350_000) // 100k + 500k - 50k - 200k
        ->and($bank->balance)->toBe(200_000);
});

it('tidak membocorkan data antar user', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();

    $this->actingAs($a);
    Account::create(['name' => 'Punya A', 'type' => 'cash']);

    $this->actingAs($b);
    expect(Account::count())->toBe(0);
});
