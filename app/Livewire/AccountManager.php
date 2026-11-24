<?php

namespace App\Livewire;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AccountManager extends Component
{
    public bool $open = false;

    public ?int $accountId = null;

    public string $name = '';

    public string $type = 'cash';

    public int $openingBalance = 0;

    public function create(): void
    {
        $this->reset('accountId', 'name', 'type', 'openingBalance');
        $this->open = true;
    }

    public function edit(int $id): void
    {
        $account = Account::findOrFail($id);
        $this->accountId = $account->id;
        $this->name = $account->name;
        $this->type = $account->type->value;
        $this->openingBalance = $account->opening_balance;
        $this->open = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::enum(AccountType::class)],
            'openingBalance' => ['required', 'integer', 'min:0'],
        ]);

        $values = ['name' => $data['name'], 'type' => $data['type'], 'opening_balance' => $data['openingBalance']];
        if ($this->accountId) {
            Account::findOrFail($this->accountId)->update($values);
        } else {
            Account::create($values);
        }

        $this->reset('accountId', 'name', 'type', 'openingBalance', 'open');
    }

    public function delete(int $id): void
    {
        $account = Account::findOrFail($id);
        $hasTransactions = Transaction::where('account_id', $id)->orWhere('to_account_id', $id)->exists();
        if ($hasTransactions) {
            $this->addError('accountId', 'Akun yang memiliki transaksi tidak bisa dihapus. Hapus atau pindahkan transaksinya dulu.');

            return;
        }

        $account->delete();
    }

    public function render()
    {
        return view('livewire.account-manager', ['accounts' => Account::orderBy('name')->get()]);
    }
}
