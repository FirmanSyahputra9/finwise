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
    public ?int $monthlyBudget = null;
    public array $analysisCategoryIds = [];

    public function toggleAnalysisCategory(int $id): void
    {
        $selected = array_map('intval', $this->analysisCategoryIds);
        $this->analysisCategoryIds = in_array($id, $selected, true)
            ? array_values(array_diff($selected, [$id]))
            : [...$selected, $id];
    }

    public function mount(): void
    {
        $this->monthlyBudget = (int) \Illuminate\Support\Facades\DB::table('monthly_budget_limits')->where('user_id', auth()->id())->where('month', now()->month)->where('year', now()->year)->value('amount') ?: null;
    }

    public function saveMonthlyBudget(): void
    {
        $data = $this->validate(['monthlyBudget' => ['required', 'integer', 'min:1']]);
        \Illuminate\Support\Facades\DB::table('monthly_budget_limits')->updateOrInsert(
            ['user_id' => auth()->id(), 'month' => now()->month, 'year' => now()->year],
            ['amount' => $data['monthlyBudget'], 'updated_at' => now(), 'created_at' => now()]
        );
    }

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
        $limit = (int) \Illuminate\Support\Facades\DB::table('monthly_budget_limits')->where('user_id', auth()->id())->where('month', $month)->where('year', $year)->value('amount');
        $allocated = (int) Budget::where('month', $month)->where('year', $year)->when($budget, fn ($q) => $q->where('id', '<>', $budget->id))->sum('amount');

        if (! $limit) {
            $this->addError('budgetAmount', 'Atur batas total budget bulanan terlebih dahulu.');
            return;
        }

        $data = $this->validate([
            'categoryId' => ['required', Rule::exists('categories', 'id')->where('user_id', auth()->id())->where('type', 'expense')],
            'budgetAmount' => ['required', 'integer', 'min:1'],
        ]);

        if ($limit && $allocated + $data['budgetAmount'] > $limit) {
            $this->addError('budgetAmount', 'Alokasi kategori melebihi batas budget bulanan yang tersisa.');
            return;
        }

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
            ->whereDate('date', '>=', $b['from'])
            ->whereDate('date', '<=', $b['to'])
            ->when($this->analysisCategoryIds, fn ($q) => $q->whereIn('category_id', $this->analysisCategoryIds))
            ->sum('amount');

        $rows = collect($this->buckets())->map(fn ($b) => [
            'label' => $b['label'],
            'income' => $sum(TransactionType::Income, $b),
            'expense' => $sum(TransactionType::Expense, $b),
        ]);
        $budgetCards = Budget::with('category')->where('month', now()->month)->where('year', now()->year)->get()
            ->map(fn (Budget $budget) => [
                'id' => $budget->id,
                'name' => $budget->category->name,
                'amount' => $budget->amount,
                'spent' => $budget->spent(),
                'percentage' => $budget->amount > 0 ? min(100, (int) round($budget->spent() / $budget->amount * 100)) : 0,
            ]);

        return view('livewire.analysis', [
            ...$this->summary(),
            'rows' => $rows,
            'max' => max(1, $rows->max(fn ($r) => max($r['income'], $r['expense']))),
            'totalIncome' => $rows->sum('income'),
            'totalExpense' => $rows->sum('expense'),
            'budgetCards' => $budgetCards,
            'expenseCategories' => Category::where('type', 'expense')->orderBy('name')->get(),
            'analysisCategories' => Category::orderBy('type')->orderBy('name')->get(),
            'monthlyBudget' => (int) \Illuminate\Support\Facades\DB::table('monthly_budget_limits')->where('user_id', auth()->id())->where('month', now()->month)->where('year', now()->year)->value('amount'),
            'allocatedBudget' => (int) Budget::where('month', now()->month)->where('year', now()->year)->sum('amount'),
        ]);
    }
}
