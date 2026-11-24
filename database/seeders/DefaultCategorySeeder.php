<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultCategorySeeder extends Seeder
{
    /** Panggil juga saat user baru register: (new DefaultCategorySeeder)->seedFor($user) */
    public function run(): void
    {
        User::each(fn (User $user) => $this->seedFor($user));
    }

    public function seedFor(User $user): void
    {
        $expense = ['Makan & Minum', 'Transportasi', 'Belanja', 'Tagihan', 'Hiburan', 'Kesehatan', 'Pendidikan', 'Lainnya'];
        $income = ['Gaji', 'Bonus', 'Usaha', 'Lainnya'];

        foreach ($expense as $name) {
            Category::withoutGlobalScopes()->firstOrCreate(['user_id' => $user->id, 'name' => $name, 'type' => 'expense']);
        }
        foreach ($income as $name) {
            Category::withoutGlobalScopes()->firstOrCreate(['user_id' => $user->id, 'name' => $name, 'type' => 'income']);
        }

        Account::withoutGlobalScopes()->firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Dompet'],
            ['type' => 'cash', 'opening_balance' => 0],
        );
    }
}
