<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\DatabaseNotification;
use App\Models\SavingsGoal;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeletionRetention
{
    public const GRACE_DAYS = 3;

    /** Permanently remove only this user's expired trash, in foreign-key order. */
    public function purgeExpired(User $user): void
    {
        $cutoff = now()->subDays(self::GRACE_DAYS);

        DB::transaction(function () use ($user, $cutoff) {
            Transaction::onlyTrashed()->where('user_id', $user->id)->where('deleted_at', '<=', $cutoff)->forceDelete();
            Budget::onlyTrashed()->where('user_id', $user->id)->where('deleted_at', '<=', $cutoff)->forceDelete();
            SavingsGoal::onlyTrashed()->where('user_id', $user->id)->where('deleted_at', '<=', $cutoff)->forceDelete();
            DatabaseNotification::onlyTrashed()
                ->where('notifiable_type', $user->getMorphClass())
                ->where('notifiable_id', $user->id)
                ->where('deleted_at', '<=', $cutoff)
                ->forceDelete();
            Category::onlyTrashed()->where('user_id', $user->id)->where('deleted_at', '<=', $cutoff)->forceDelete();
            Account::onlyTrashed()->where('user_id', $user->id)->where('deleted_at', '<=', $cutoff)->forceDelete();
        });
    }

    public function recentlyDeleted(User $user): Collection
    {
        $cutoff = now()->subDays(self::GRACE_DAYS);
        $items = collect();

        foreach (Account::onlyTrashed()->where('user_id', $user->id)->where('deleted_at', '>', $cutoff)->get() as $model) {
            $items->push($this->item('account', $model->id, __('ui.deleted_item_account', ['name' => $model->name]), $model->deleted_at));
        }
        foreach (Category::onlyTrashed()->where('user_id', $user->id)->where('deleted_at', '>', $cutoff)->get() as $model) {
            $items->push($this->item('category', $model->id, __('ui.deleted_item_category', ['name' => $model->name]), $model->deleted_at));
        }
        foreach (Transaction::onlyTrashed()->where('user_id', $user->id)->where('deleted_at', '>', $cutoff)->get() as $model) {
            $items->push($this->item('transaction', $model->id, __('ui.deleted_item_transaction', ['type' => $model->type->label(), 'amount' => \App\Support\Money::rp($model->amount)]), $model->deleted_at));
        }
        foreach (Budget::onlyTrashed()->where('user_id', $user->id)->where('deleted_at', '>', $cutoff)->get() as $model) {
            $category = Category::withTrashed()->whereKey($model->category_id)->value('name');
            $items->push($this->item('budget', $model->id, __('ui.deleted_item_budget', ['name' => $category ?? '—']), $model->deleted_at));
        }
        foreach (SavingsGoal::onlyTrashed()->where('user_id', $user->id)->where('deleted_at', '>', $cutoff)->get() as $model) {
            $items->push($this->item('goal', $model->id, __('ui.deleted_item_goal', ['name' => $model->name]), $model->deleted_at));
        }
        foreach ($user->notifications()->onlyTrashed()->where('deleted_at', '>', $cutoff)->get() as $model) {
            $items->push($this->item('notification', $model->id, $model->data['title'] ?? 'Notification', $model->deleted_at));
        }

        return $items->sortByDesc('deletedAt')->values();
    }

    private function item(string $type, int|string $id, string $label, mixed $deletedAt): array
    {
        return compact('type', 'id', 'label', 'deletedAt');
    }

    public function softDeleteCategory(Category $category): void
    {
        $deletedAt = now();
        DB::transaction(function () use ($category, $deletedAt) {
            Budget::where('user_id', $category->user_id)
                ->where('category_id', $category->id)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => $deletedAt, 'updated_at' => $deletedAt]);
            $category->deleted_at = $deletedAt;
            $category->save();
        });
    }

    public function restore(string $type, int|string $id, User $user): Model
    {
        $classes = [
            'account' => Account::class,
            'category' => Category::class,
            'transaction' => Transaction::class,
            'budget' => Budget::class,
            'goal' => SavingsGoal::class,
        ];

        if ($type === 'notification') {
            $notification = $user->notifications()->withTrashed()->whereKey($id)->firstOrFail();
            abort_unless($notification->trashed() && $notification->deleted_at->gt(now()->subDays(self::GRACE_DAYS)), 404);
            $notification->restore();

            return $notification;
        }

        abort_unless(isset($classes[$type]), 404);
        $class = $classes[$type];
        $model = $class::withTrashed()->where('user_id', $user->id)->whereKey($id)->firstOrFail();
        abort_unless($model->trashed() && $model->deleted_at->gt(now()->subDays(self::GRACE_DAYS)), 404);
        $deletedAt = $model->deleted_at;
        $model->restore();

        if ($type === 'category') {
            Budget::onlyTrashed()
                ->where('user_id', $user->id)
                ->where('category_id', $model->id)
                ->where('deleted_at', $deletedAt)
                ->restore();
        }

        return $model;
    }

    public function permanentlyDelete(string $type, int|string $id, User $user): void
    {
        DB::transaction(function () use ($type, $id, $user) {
            if ($type === 'notification') {
                $notification = $user->notifications()->withTrashed()->whereKey($id)->firstOrFail();
                abort_unless($notification->trashed(), 404);
                $notification->forceDelete();

                return;
            }

            $classes = [
                'account' => Account::class,
                'category' => Category::class,
                'transaction' => Transaction::class,
                'budget' => Budget::class,
                'goal' => SavingsGoal::class,
            ];
            abort_unless(isset($classes[$type]), 404);

            $class = $classes[$type];
            $model = $class::onlyTrashed()
                ->where('user_id', $user->id)
                ->whereKey($id)
                ->firstOrFail();
            $model->forceDelete();
        });
    }

    public function permanentlyDeleteAll(User $user): void
    {
        DB::transaction(function () use ($user) {
            Transaction::onlyTrashed()->where('user_id', $user->id)->forceDelete();
            Budget::onlyTrashed()->where('user_id', $user->id)->forceDelete();
            SavingsGoal::onlyTrashed()->where('user_id', $user->id)->forceDelete();
            $user->notifications()->onlyTrashed()->forceDelete();
            Category::onlyTrashed()->where('user_id', $user->id)->forceDelete();
            Account::onlyTrashed()->where('user_id', $user->id)->forceDelete();
        });
    }
}
