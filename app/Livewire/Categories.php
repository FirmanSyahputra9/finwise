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
        return view('livewire.categories', [
            ...$this->summary(),
            'categories' => Category::where('type', 'expense')->orderBy('name')->get(),
        ]);
    }
}
