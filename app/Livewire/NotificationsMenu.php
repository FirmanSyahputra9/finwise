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

    public function deleteAll(): void
    {
        auth()->user()->notifications()->update(['deleted_at' => now()]);
    }

    public function delete(string $id): void
    {
        $notification = $this->notification($id);
        $notification?->delete();
        if ($notification) {
            $this->dispatch('deletion-made', type: 'notification', id: $notification->id);
        }
    }

    private function notification(string $id): ?DatabaseNotification
    {
        return auth()->user()->notifications()->whereKey($id)->first();
    }

    public function render()
    {
        $notifications = auth()->user()->notifications()->latest()->limit(8)->get();
        $legacyTypes = ['Pemasukan' => 'income', 'Pengeluaran' => 'expense', 'Transfer' => 'transfer'];
        foreach ($notifications as $notification) {
            $data = $notification->data;
            if (isset($data['title_key'])) {
                continue;
            }

            $titleKey = match ($data['title'] ?? '') {
                'Transaksi berhasil disimpan' => 'notification_saved',
                'Transaksi offline tersinkron' => 'notification_offline_synced',
                'Budget terlampaui' => 'notification_budget_reached',
                'Budget hampir habis' => 'notification_budget_low',
                default => null,
            };
            if (! $titleKey) {
                continue;
            }

            $data['title_key'] = $titleKey;
            $message = $data['message'] ?? '';
            if (preg_match('/^(Pemasukan|Pengeluaran|Transfer) sebesar (Rp [\d.]+) (?:telah dicatat|berhasil disimpan)\.$/u', $message, $matches)) {
                $data['message_key'] = $titleKey === 'notification_offline_synced' ? 'notification_offline_recorded' : 'notification_recorded';
                $data['message_params'] = ['type' => $legacyTypes[$matches[1]], 'amount' => $matches[2]];
            } elseif (preg_match('/^Pengeluaran kategori (.+) mencapai (Rp [\d.]+) dari budget (Rp [\d.]+)\.$/u', $message, $matches)) {
                $data['message_key'] = 'notification_budget_detail';
                $data['message_params'] = ['category' => $matches[1], 'spent' => $matches[2], 'budget' => $matches[3]];
            }
            $notification->data = $data;
        }

        return view('livewire.notifications-menu', [
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }
}
