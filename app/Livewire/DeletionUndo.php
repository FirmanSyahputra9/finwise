<?php

namespace App\Livewire;

use App\Support\DeletionRetention;
use Livewire\Attributes\On;
use Livewire\Component;

class DeletionUndo extends Component
{
    public ?string $type = null;

    public ?string $recordId = null;

    #[On('deletion-made')]
    public function show(string $type, int|string $id): void
    {
        $this->type = $type;
        $this->recordId = (string) $id;
    }

    #[On('deletion-undone')]
    public function clear(): void
    {
        $this->reset('type', 'recordId');
    }

    public function undo(DeletionRetention $retention): void
    {
        if (! $this->type || ! $this->recordId) {
            return;
        }

        $retention->restore($this->type, $this->recordId, auth()->user());
        $this->reset('type', 'recordId');
        $this->dispatch('deletion-undone');
    }

    public function render()
    {
        return view('livewire.deletion-undo');
    }
}
