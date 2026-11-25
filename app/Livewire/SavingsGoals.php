<?php

namespace App\Livewire;

use App\Livewire\Concerns\HasSummary;
use App\Models\SavingsGoal as Goal;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SavingsGoals extends Component
{
    use HasSummary;

    public bool $formOpen = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $targetAmount = '';
    public string $targetDate = '';
    public string $contribution = '';

    public function create(): void
    {
        $this->resetForm();
        $this->dispatch('app-toast', message: __('ui.saved'));
        $this->formOpen = true;
    }

    public function edit(int $id): void
    {
        $goal = Goal::findOrFail($id);
        $this->editingId = $goal->id;
        $this->name = $goal->name;
        $this->targetAmount = (string) $goal->target_amount;
        $this->targetDate = $goal->target_date?->format('Y-m-d') ?? '';
        $this->formOpen = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'targetAmount' => ['required', 'integer', 'min:1', 'max:9999999999999'],
            'targetDate' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $data = ['name' => $this->name, 'target_amount' => (int) $this->targetAmount, 'target_date' => $this->targetDate ?: null];
        if ($this->editingId) {
            Goal::findOrFail($this->editingId)->update($data);
        } else {
            Goal::create($data);
        }

        $this->resetForm();
    }

    public function addContribution(int $id): void
    {
        $this->validate(['contribution' => ['required', 'integer', 'min:1', 'max:9999999999999']]);
        $goal = Goal::findOrFail($id);
        $goal->saved_amount += (int) $this->contribution;
        $goal->save();
        $this->reset('contribution');
        $this->dispatch('app-toast', message: __('ui.saved'));
    }

    public function delete(int $id): void
    {
        $goal = Goal::find($id);
        if (! $goal) {
            return;
        }

        $goal->delete();
        $this->dispatch('deletion-made', type: 'goal', id: $goal->id);
    }

    private function resetForm(): void
    {
        $this->reset(['formOpen', 'editingId', 'name', 'targetAmount', 'targetDate']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.savings-goals', [
            ...$this->summary(),
            'goals' => Goal::orderByRaw('target_date IS NULL')->orderBy('target_date')->latest('id')->get(),
        ]);
    }
}
