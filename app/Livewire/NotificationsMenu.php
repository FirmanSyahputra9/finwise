<?php

namespace App\Livewire;

use Illuminate\Notifications\DatabaseNotification;
use Livewire\Component;

class NotificationsMenu extends Component
{
    public bool $open = false;

    public function markRead(string $id): void
    {
        $this->notification($id)?->markAsRead();
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function delete(string $id): void
    {
        $this->notification($id)?->delete();
    }

    private function notification(string $id): ?DatabaseNotification
    {
        return auth()->user()->notifications()->whereKey($id)->first();
    }

    public function render()
    {
        return view('livewire.notifications-menu', [
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
            'notifications' => auth()->user()->notifications()->latest()->limit(8)->get(),
        ]);
    }
}
