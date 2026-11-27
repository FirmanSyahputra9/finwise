<?php

namespace App\Livewire;

use App\Livewire\Concerns\HasSummary;
use App\Models\Category;
use App\Support\DeletionRetention;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Categories extends Component
{
    use HasSummary;

    public string $name = '';
    public string $categoryType = 'expense';
    public ?int $selectedCategory = null;
    public string $typeFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function mount(): void
    {
        $this->dateTo = now()->toDateString();
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:40'],
            'categoryType' => ['required', 'in:income,expense'],
        ]);

        Category::create(['name' => $this->name, 'type' => $this->categoryType]);

        $this->reset('name');
        $this->dispatch('saved');
        $this->dispatch('app-toast', message: __('ui.saved'));
    }

    public function updatedCategoryType(): void
    {
        $this->reset('selectedCategory');
    }

    public function delete(int $id): void
    {
        $category = Category::find($id);
        if (! $category) {
            return;
        }

        app(DeletionRetention::class)->softDeleteCategory($category);
        $this->dispatch('deletion-made', type: 'category', id: $category->id);
    }

    public function render()
    {
        $transactions = $this->selectedCategory ? \App\Models\Transaction::with('account')->where('category_id', $this->selectedCategory)
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('date', '<=', $this->dateTo))
            ->latest('date')->get() : collect();
        return view('livewire.categories', [
            ...$this->summary(),
            'categories' => Category::where('type', $this->categoryType)->orderBy('name')->get(),
            'transactions' => $transactions,
            'selectedCategoryName' => Category::find($this->selectedCategory)?->name,
        ]);
    }
}
