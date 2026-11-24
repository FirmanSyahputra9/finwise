<?php

namespace App\Livewire;

use App\Livewire\Concerns\HasSummary;
use App\Models\Category;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Categories extends Component
{
    use HasSummary;

    public string $name = '';
    public ?int $selectedCategory = null;
    public string $typeFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public function save(): void
    {
        $this->validate(['name' => ['required', 'string', 'max:40']]);

        Category::create(['name' => $this->name, 'type' => 'expense']);

        $this->reset('name');
        $this->dispatch('saved');
    }

    public function delete(int $id): void
    {
        Category::findOrFail($id)->delete();
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
            'categories' => Category::where('type', 'expense')->orderBy('name')->get(),
            'transactions' => $transactions,
            'selectedCategoryName' => Category::find($this->selectedCategory)?->name,
        ]);
    }
}
