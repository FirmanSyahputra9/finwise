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

    private const CHART_COLORS = ['#00D09E', '#3984D8', '#F0A24D', '#9272DC', '#E779AD', '#E66E70', '#00A982', '#F3C84B'];

    public string $period = 'daily';

    public string $chartType = 'bar';

    public string $averageMode = 'all';

    public bool $budgetModal = false;

    public ?int $budgetId = null;

    public ?int $categoryId = null;

    public ?int $budgetAmount = null;
    public ?int $monthlyBudget = null;

    public string $analysisCategoryType = 'income';

    /** @var array<int, int|string> */
    public array $analysisIncomeCategoryIds = [];

    /** @var array<int, int|string> */
    public array $analysisExpenseCategoryIds = [];

    /** @deprecated Kept as an alias for existing Livewire callers; contains expense category IDs. */
    public array $analysisCategoryIds = [];

    public function toggleAnalysisCategory(int $id): void
    {
        $category = Category::whereKey($id)->firstOrFail();
        if ($category->type === TransactionType::Income) {
            $this->analysisIncomeCategoryIds = $this->toggleCategoryId($this->analysisIncomeCategoryIds, $id);

            return;
        }

        $this->analysisExpenseCategoryIds = $this->toggleCategoryId($this->analysisExpenseCategoryIds, $id);
        $this->analysisCategoryIds = $this->analysisExpenseCategoryIds;
    }

    /** @param array<int, int|string> $selected @return array<int, int> */
    private function toggleCategoryId(array $selected, int $id): array
    {
        $selected = array_map('intval', $selected);

        return in_array($id, $selected, true)
            ? array_values(array_diff($selected, [$id]))
            : [...$selected, $id];
    }

    public function clearAnalysisCategories(string $type): void
    {
        abort_unless(in_array($type, ['income', 'expense'], true), 404);

        if ($type === 'income') {
            $this->analysisIncomeCategoryIds = [];

            return;
        }

        $this->analysisExpenseCategoryIds = [];
        $this->analysisCategoryIds = [];
    }

    public function setAnalysisCategoryType(string $type): void
    {
        abort_unless(in_array($type, ['income', 'expense'], true), 404);

        $this->analysisCategoryType = $type;
    }

    public function updatedAverageMode(string $mode): void
    {
        if (! in_array($mode, ['all', 'active'], true)) {
            $this->averageMode = 'all';
        }
    }

    public function updatedChartType(string $type): void
    {
        if (! in_array($type, ['bar', 'line', 'pie'], true)) {
            $this->chartType = 'bar';
        }
    }

    public function mount(): void
    {
        $this->monthlyBudget = (int) \Illuminate\Support\Facades\DB::table('monthly_budget_limits')->where('user_id', auth()->id())->where('month', now()->month)->where('year', now()->year)->value('amount') ?: null;
    }

    public function saveMonthlyBudget(): void
    {
        $data = $this->validate(['monthlyBudget' => ['required', 'integer', 'min:1', 'max:9999999999999']]);
        $allocated = (int) Budget::where('month', now()->month)->where('year', now()->year)->sum('amount');

        if ($allocated > $data['monthlyBudget']) {
            $this->addError('monthlyBudget', __('ui.monthly_below_allocations', ['amount' => number_format($allocated, 0, ',', '.') ]));
            return;
        }

        \Illuminate\Support\Facades\DB::table('monthly_budget_limits')->updateOrInsert(
            ['user_id' => auth()->id(), 'month' => now()->month, 'year' => now()->year],
            ['amount' => $data['monthlyBudget'], 'updated_at' => now(), 'created_at' => now()]
        );
        $this->dispatch('app-toast', message: __('ui.saved'));
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
        $trashedBudget = $budget ? null : Budget::onlyTrashed()
            ->where('category_id', $this->categoryId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();
        $limit = (int) \Illuminate\Support\Facades\DB::table('monthly_budget_limits')->where('user_id', auth()->id())->where('month', $month)->where('year', $year)->value('amount');
        $allocated = (int) Budget::where('month', $month)->where('year', $year)->when($budget, fn ($q) => $q->where('id', '<>', $budget->id))->sum('amount');

        if (! $limit) {
            $this->addError('budgetAmount', __('ui.set_monthly_limit_first'));
            return;
        }

        $data = $this->validate([
            'categoryId' => ['required', Rule::exists('categories', 'id')->where('user_id', auth()->id())->where('type', 'expense')->whereNull('deleted_at')],
            'budgetAmount' => ['required', 'integer', 'min:1', 'max:9999999999999'],
        ]);

        if ($limit && $allocated + $data['budgetAmount'] > $limit) {
            $this->addError('budgetAmount', __('ui.allocation_exceeds_limit'));
            return;
        }

        validator(['categoryId' => $data['categoryId']], [
            'categoryId' => [Rule::unique('budgets', 'category_id')->where('user_id', auth()->id())->where('month', $month)->where('year', $year)->ignore($budget?->id ?? $trashedBudget?->id)],
        ], ['categoryId.unique' => __('ui.category_budget_exists')])->validate();

        if ($budget) {
            $budget->update(['category_id' => $data['categoryId'], 'amount' => $data['budgetAmount']]);
        } elseif ($trashedBudget) {
            $trashedBudget->restore();
            $trashedBudget->update(['amount' => $data['budgetAmount']]);
        } else {
            Budget::create(['category_id' => $data['categoryId'], 'amount' => $data['budgetAmount'], 'month' => $month, 'year' => $year]);
        }

        $this->budgetModal = false;
        $this->reset('budgetId', 'categoryId', 'budgetAmount');
        $this->dispatch('app-toast', message: __('ui.saved'));
    }

    public function deleteBudget(int $id): void
    {
        $budget = Budget::whereKey($id)->first();
        if (! $budget) {
            return;
        }

        $budget->delete();
        $this->dispatch('deletion-made', type: 'budget', id: $budget->id);
    }

    private function buckets(): array
    {
        $make = fn ($label, $from, $to) => ['label' => $label, 'from' => $from->toDateString(), 'to' => $to->toDateString()];

        return match ($this->period) {
            'weekly' => collect(range(3, 0))->map(fn ($i) => $make(__('ui.weekly_short').' '.(4 - $i), now()->subWeeks($i)->startOfWeek(), now()->subWeeks($i)->endOfWeek()))->all(),
            'monthly' => collect(range(5, 0))->map(fn ($i) => $make(now()->subMonthsNoOverflow($i)->translatedFormat('M'), now()->subMonthsNoOverflow($i)->startOfMonth(), now()->subMonthsNoOverflow($i)->endOfMonth()))->all(),
            'yearly' => collect(range(4, 0))->map(fn ($i) => $make((string) now()->subYears($i)->year, now()->subYears($i)->startOfYear(), now()->subYears($i)->endOfYear()))->all(),
            default => collect(range(6, 0))->map(fn ($i) => $make(now()->subDays($i)->translatedFormat('D'), now()->subDays($i)->startOfDay(), now()->subDays($i)->endOfDay()))->all(),
        };
    }

    public function render()
    {
        $buckets = collect($this->buckets());
        $sum = fn (TransactionType $type, array $categoryIds, array $b) => (int) Transaction::ofType($type)
            ->whereDate('date', '>=', $b['from'])
            ->whereDate('date', '<=', $b['to'])
            ->when($categoryIds, fn ($q) => $q->whereIn('category_id', $categoryIds))
            ->sum('amount');

        $rows = $this->chartType === 'pie'
            ? collect()
            : $buckets->map(fn ($b) => [
                'label' => $b['label'],
                'income' => $sum(TransactionType::Income, $this->analysisIncomeCategoryIds, $b),
                'expense' => $sum(TransactionType::Expense, $this->analysisExpenseCategoryIds, $b),
            ]);
        $bucketCount = max(1, $rows->count());
        $incomeBucketCount = max(1, $rows->where('income', '>', 0)->count());
        $expenseBucketCount = max(1, $rows->where('expense', '>', 0)->count());
        $max = max(1, $rows->max(fn ($r) => max($r['income'], $r['expense'])));
        $chartCoordinates = $rows->values()->map(function (array $row, int $index) use ($rows, $max): array {
            $x = $rows->count() > 1 ? (int) round(8 + $index * 264 / ($rows->count() - 1)) : 140;

            return [
                'x' => $x,
                'incomeY' => 126 - (int) round($row['income'] / $max * 116),
                'expenseY' => 126 - (int) round($row['expense'] / $max * 116),
            ];
        });
        $incomeLinePoints = $chartCoordinates->map(fn (array $point) => $point['x'].','.$point['incomeY'])->implode(' ');
        $expenseLinePoints = $chartCoordinates->map(fn (array $point) => $point['x'].','.$point['expenseY'])->implode(' ');
        $pieCategories = collect();
        $pieTotal = 0;
        $pieCircumference = 2 * M_PI * 40;

        if ($this->chartType === 'pie') {
            $type = $this->analysisCategoryType === 'income' ? TransactionType::Income : TransactionType::Expense;
            $categoryIds = $this->analysisCategoryType === 'income'
                ? $this->analysisIncomeCategoryIds
                : $this->analysisExpenseCategoryIds;
            $pieTotals = Transaction::ofType($type)
                ->whereDate('date', '>=', $buckets->first()['from'])
                ->whereDate('date', '<=', $buckets->last()['to'])
                ->when($categoryIds, fn ($query) => $query->whereIn('category_id', $categoryIds))
                ->selectRaw('category_id, SUM(amount) as total')
                ->groupBy('category_id')
                ->pluck('total', 'category_id')
                ->sortDesc();
            $categoriesById = Category::withTrashed()->whereIn('id', $pieTotals->keys())->get()->keyBy('id');
            $pieCategories = $pieTotals->map(function ($amount, $categoryId) use ($categoriesById): ?array {
                $category = $categoriesById->get($categoryId);

                return $category ? ['name' => $category->name, 'amount' => (int) $amount] : null;
            })->filter()->values();
            $pieTotal = (int) $pieCategories->sum('amount');
            $offset = 0.0;

            $pieCategories = $pieCategories->map(function (array $category, int $index) use ($pieTotal, $pieCircumference, &$offset): array {
                $length = $pieTotal > 0 ? $pieCircumference * $category['amount'] / $pieTotal : 0;
                $slice = [
                    ...$category,
                    'color' => self::CHART_COLORS[$index % count(self::CHART_COLORS)],
                    'length' => $length,
                    'offset' => $offset,
                ];
                $offset += $length;

                return $slice;
            });
        }

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
            'max' => $max,
            'chartType' => $this->chartType,
            'chartCoordinates' => $chartCoordinates,
            'incomeLinePoints' => $incomeLinePoints,
            'expenseLinePoints' => $expenseLinePoints,
            'pieCategories' => $pieCategories,
            'pieTotal' => $pieTotal,
            'pieCircumference' => $pieCircumference,
            'totalIncome' => $rows->sum('income'),
            'totalExpense' => $rows->sum('expense'),
            'averageIncome' => intdiv((int) $rows->sum('income'), $this->averageMode === 'active' ? $incomeBucketCount : $bucketCount),
            'averageExpense' => intdiv((int) $rows->sum('expense'), $this->averageMode === 'active' ? $expenseBucketCount : $bucketCount),
            'averageMode' => $this->averageMode,
            'budgetCards' => $budgetCards,
            'expenseCategories' => Category::where('type', 'expense')->orderBy('name')->get(),
            'analysisIncomeCategories' => Category::where('type', 'income')->orderBy('name')->get(),
            'analysisExpenseCategories' => Category::where('type', 'expense')->orderBy('name')->get(),
            'selectedAnalysisIncomeCategories' => Category::whereIn('id', $this->analysisIncomeCategoryIds)->orderBy('name')->pluck('name')->all(),
            'selectedAnalysisExpenseCategories' => Category::whereIn('id', $this->analysisExpenseCategoryIds)->orderBy('name')->pluck('name')->all(),
            'monthlyBudget' => (int) \Illuminate\Support\Facades\DB::table('monthly_budget_limits')->where('user_id', auth()->id())->where('month', now()->month)->where('year', now()->year)->value('amount'),
            'allocatedBudget' => (int) Budget::where('month', now()->month)->where('year', now()->year)->sum('amount'),
        ]);
    }
}
