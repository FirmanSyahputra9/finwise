<?php

namespace App\Livewire;

use App\Support\DeletionRetention;
use Livewire\Component;

class DeletedItems extends Component
{
    public function undo(string $type, int|string $id, DeletionRetention $retention): void
    {
        $retention->restore($type, $id, auth()->user());
        $this->dispatch('deletion-undone');
    }

    public function permanentlyDelete(string $type, int|string $id, DeletionRetention $retention): void
    {
        $retention->permanentlyDelete($type, $id, auth()->user());
    }

    public function permanentlyDeleteAll(DeletionRetention $retention): void
    {
        $retention->permanentlyDeleteAll(auth()->user());
    }

    public function render(DeletionRetention $retention)
    {
        return view('livewire.deleted-items', [
            'items' => $retention->recentlyDeleted(auth()->user()),
        ]);
    }
}
