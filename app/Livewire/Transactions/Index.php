<?php

namespace App\Livewire\Transactions;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Budget;
use App\Notifications\FinanceActivityNotification;
use App\Support\OfflineFinanceSnapshot;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $type = 'expense';

    public ?int $account_id = null;

    public ?int $to_account_id = null;

    public ?int $category_id = null;

    public ?int $amount = null;

    public string $date = '';

    public string $note = '';

    public string $filterType = '';

    public string $search = '';

    public string $filterAccount = '';

    public string $filterCategory = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->account_id = Account::value('id');
    }

    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'account_id' => ['required', Rule::exists('accounts', 'id')->where('user_id', auth()->id())->whereNull('deleted_at')],
            'to_account_id' => [
                Rule::requiredIf($this->type === 'transfer'),
                'nullable', 'different:account_id',
                Rule::exists('accounts', 'id')->where('user_id', auth()->id())->whereNull('deleted_at'),
            ],
            'category_id' => [
                Rule::requiredIf($this->type !== 'transfer'),
                'nullable',
                Rule::exists('categories', 'id')->where('user_id', auth()->id())->where('type', $this->type)->whereNull('deleted_at'),
            ],
            'amount' => ['required', 'integer', 'min:1', 'max:9999999999999'],
            'date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($data['type'] === 'transfer') {
            $data['category_id'] = null;
        } else {
            $data['to_account_id'] = null;
        }

        $budget = null;
        $spentBefore = 0;
        if ($data['type'] === 'expense') {
            $date = \Illuminate\Support\Carbon::parse($data['date']);
            $budget = Budget::with('category')->where('category_id', $data['category_id'])
                ->where('month', $date->month)->where('year', $date->year)->first();
            $spentBefore = $budget?->spent() ?? 0;
        }

        if ($this->editingId) {
            Transaction::findOrFail($this->editingId)->update($data);
            $this->dispatch('transaction-saved');
            $this->dispatch('app-toast', message: __('ui.saved'));
            $this->reset('editingId', 'amount', 'note', 'category_id', 'to_account_id');
            $this->resetPage();

            return;
        }

        Transaction::create($data);
        $this->dispatch('app-toast', message: __('ui.saved'));

        $amount = (int) $data['amount'];
        $rp = fn (int $value) => 'Rp '.number_format($value, 0, ',', '.');
        auth()->user()->notify(new FinanceActivityNotification([
            'title_key' => 'notification_saved',
            'message_key' => 'notification_recorded',
            'message_params' => ['type' => $data['type'], 'amount' => $rp($amount)],
            'title' => __('ui.notification_saved'),
            'message' => __('ui.notification_recorded', ['type' => __('ui.'.$data['type']), 'amount' => $rp($amount)]),
        ]));
        $this->dispatch('finance-notification', title: __('ui.notification_saved'), body: __('ui.notification_recorded', ['type' => __('ui.'.$data['type']), 'amount' => $rp($amount)]));

        if ($budget && $budget->amount > 0) {
            $spentAfter = $spentBefore + $amount;
            if ($spentBefore < $budget->amount && $spentAfter >= $budget->amount) {
                auth()->user()->notify(new FinanceActivityNotification([
                    'title_key' => 'notification_budget_reached',
                    'message_key' => 'notification_budget_detail',
                    'message_params' => ['category' => $budget->category->name, 'spent' => $rp($spentAfter), 'budget' => $rp($budget->amount)],
                    'title' => __('ui.notification_budget_reached'),
                    'message' => __('ui.notification_budget_detail', ['category' => $budget->category->name, 'spent' => $rp($spentAfter), 'budget' => $rp($budget->amount)]),
                ]));
            } elseif ($spentBefore < $budget->amount * 0.8 && $spentAfter >= $budget->amount * 0.8) {
                auth()->user()->notify(new FinanceActivityNotification([
                    'title_key' => 'notification_budget_low',
                    'message_key' => 'notification_budget_detail',
                    'message_params' => ['category' => $budget->category->name, 'spent' => $rp($spentAfter), 'budget' => $rp($budget->amount)],
                    'title' => __('ui.notification_budget_low'),
                    'message' => __('ui.notification_budget_detail', ['category' => $budget->category->name, 'spent' => $rp($spentAfter), 'budget' => $rp($budget->amount)]),
                ]));
            }
        }

        $this->reset('amount', 'note', 'category_id', 'to_account_id');
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $transaction = Transaction::find($id);
        if (! $transaction) {
            return;
        }

        $transaction->delete();
        $this->dispatch('deletion-made', type: 'transaction', id: $transaction->id);
    }

    public function edit(int $id): void
    {
        $transaction = Transaction::findOrFail($id);
        $this->editingId = $transaction->id;
        $this->type = $transaction->type->value;
        $this->account_id = $transaction->account_id;
        $this->to_account_id = $transaction->to_account_id;
        $this->category_id = $transaction->category_id;
        $this->amount = $transaction->amount;
        $this->date = $transaction->date->toDateString();
        $this->note = $transaction->note ?? '';
        $this->dispatch('edit-transaction');
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'amount', 'note', 'category_id', 'to_account_id');
        $this->resetValidation();
    }

    public function resetFilters(): void
    {
        $this->reset('filterType', 'search', 'filterAccount', 'filterCategory', 'dateFrom', 'dateTo');
        $this->dateTo = now()->toDateString();
        $this->resetPage();
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(string $type): void
    {
        if ($this->filterCategory) {
            $categoryMatches = Category::whereKey($this->filterCategory)
                ->where('type', $type)
                ->exists();

            if (! $categoryMatches) {
                $this->filterCategory = '';
            }
        }
    }

    public function render()
    {
        $accounts = Account::all();
        $allCategories = Category::orderBy('name')->get();
        $filterCategories = Category::whereHas('transactions', fn ($query) => $query
            ->when($this->filterType, fn ($transactions) => $transactions->where('type', $this->filterType)))
            ->when(in_array($this->filterType, ['income', 'expense'], true), fn ($query) => $query->where('type', $this->filterType))
            ->when($this->filterType === 'transfer', fn ($query) => $query->whereRaw('1 = 0'))
            ->orderBy('name')
            ->get();
        $filterAccounts = Account::where(function ($query) {
            $query->whereHas('transactions')
                ->orWhereIn('id', Transaction::whereNotNull('to_account_id')->select('to_account_id'));
        })->orderBy('name')->get();
        $filterTypes = Transaction::query()->select('type')->distinct()->orderBy('type')->pluck('type')
            ->map(fn ($type) => $type instanceof TransactionType ? $type->value : (string) $type)
            ->all();
        $offlineDataJson = json_encode(OfflineFinanceSnapshot::forCurrentUser(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        $transactions = Transaction::with(['category', 'account', 'toAccount'])
            ->when($this->filterType, fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterAccount, fn ($q) => $q->where(fn ($query) => $query->where('account_id', $this->filterAccount)->orWhere('to_account_id', $this->filterAccount)))
            ->when($this->filterCategory, fn ($q) => $q->where('category_id', $this->filterCategory))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('date', '<=', $this->dateTo))
            ->when(trim($this->search) !== '', fn ($q) => $q->where(fn ($query) => $query
                ->where('note', 'like', '%'.trim($this->search).'%')
                ->orWhereHas('category', fn ($category) => $category->where('name', 'like', '%'.trim($this->search).'%'))
                ->orWhereHas('account', fn ($account) => $account->where('name', 'like', '%'.trim($this->search).'%'))
                ->orWhereHas('toAccount', fn ($account) => $account->where('name', 'like', '%'.trim($this->search).'%'))))
            ->latest('date')->latest('id')
            ->paginate(15);

        return view('livewire.transactions.index', [
            'transactions' => $transactions,
            'accounts' => $accounts,
            'categories' => Category::where('type', $this->type)->get(),
            'allCategories' => $allCategories,
            'filterCategories' => $filterCategories,
            'filterAccounts' => $filterAccounts,
            'filterTypes' => $filterTypes,
            'offlineDataJson' => $offlineDataJson,
        ]);
    }
}
