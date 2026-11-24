<?php

namespace App\Livewire;

use App\Enums\TransactionType;
use App\Livewire\Concerns\HasSummary;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Analysis extends Component
{
    use HasSummary;

    public string $period = 'daily';

    public bool $budgetModal = false;

    public ?int $budgetId = null;

    public ?int $categoryId = null;

    public ?int $budgetAmount = null;

    public function createBudget(): void
    {
        $this->reset('budgetId', 'categoryId', 'budgetAmount');
        $this->budgetModal = true;
    }

    public function editBudget(int $id): void
    {
        $budget = Budget::whereKey($id)->firstOrFail();
        $this->budgetId = $budget->id;
        $this->categoryId = $budget->category_id;
        $this->budgetAmount = $budget->amount;
        $this->budgetModal = true;
    }

    public function saveBudget(): void
    {
        $budget = $this->budgetId ? Budget::whereKey($this->budgetId)->firstOrFail() : null;
        $month = now()->month;
        $year = now()->year;

        $data = $this->validate([
            'categoryId' => ['required', Rule::exists('categories', 'id')->where('user_id', auth()->id())->where('type', 'expense')],
            'budgetAmount' => ['required', 'integer', 'min:1'],
        ]);

        validator(['categoryId' => $data['categoryId']], [
            'categoryId' => [Rule::unique('budgets', 'category_id')->where('user_id', auth()->id())->where('month', $month)->where('year', $year)->ignore($budget?->id)],
        ], ['categoryId.unique' => 'Kategori ini sudah memiliki budget bulan ini.'])->validate();

        if ($budget) {
            $budget->update(['category_id' => $data['categoryId'], 'amount' => $data['budgetAmount']]);
        } else {
            Budget::create(['category_id' => $data['categoryId'], 'amount' => $data['budgetAmount'], 'month' => $month, 'year' => $year]);
        }

        $this->budgetModal = false;
        $this->reset('budgetId', 'categoryId', 'budgetAmount');
    }

    public function deleteBudget(int $id): void
    {
        Budget::whereKey($id)->firstOrFail()->delete();
    }

    private function buckets(): array
    {
        $make = fn ($label, $from, $to) => ['label' => $label, 'from' => $from->toDateString(), 'to' => $to->toDateString()];

        return match ($this->period) {
            'weekly' => collect(range(3, 0))->map(fn ($i) => $make('Wk '.(4 - $i), now()->subWeeks($i)->startOfWeek(), now()->subWeeks($i)->endOfWeek()))->all(),
            'monthly' => collect(range(5, 0))->map(fn ($i) => $make(now()->subMonthsNoOverflow($i)->translatedFormat('M'), now()->subMonthsNoOverflow($i)->startOfMonth(), now()->subMonthsNoOverflow($i)->endOfMonth()))->all(),
            'yearly' => collect(range(4, 0))->map(fn ($i) => $make((string) now()->subYears($i)->year, now()->subYears($i)->startOfYear(), now()->subYears($i)->endOfYear()))->all(),
            default => collect(range(6, 0))->map(fn ($i) => $make(now()->subDays($i)->translatedFormat('D'), now()->subDays($i)->startOfDay(), now()->subDays($i)->endOfDay()))->all(),
        };
    }

    public function render()
    {
        $sum = fn (TransactionType $type, array $b) => (int) Transaction::ofType($type)
            ->whereBetween('date', [$b['from'], $b['to']])->sum('amount');

        $rows = collect($this->buckets())->map(fn ($b) => [
            'label' => $b['label'],
            'income' => $sum(TransactionType::Income, $b),
            'expense' => $sum(TransactionType::Expense, $b),
        ]);

        return view('livewire.analysis', [
            ...$this->summary(),
            'rows' => $rows,
            'max' => max(1, $rows->max(fn ($r) => max($r['income'], $r['expense']))),
            'totalIncome' => $rows->sum('income'),
            'totalExpense' => $rows->sum('expense'),
            'targets' => Budget::with('category')
                ->where('month', now()->month)->where('year', now()->year)->get(),
            'expenseCategories' => Category::where('type', 'expense')->orderBy('name')->get(),
        ]);
    }
}
