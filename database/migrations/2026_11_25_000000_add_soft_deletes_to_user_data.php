<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['accounts', 'categories', 'transactions', 'budgets', 'savings_goals', 'notifications'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->softDeletes();
                if ($table === 'notifications') {
                    $blueprint->index(['notifiable_type', 'notifiable_id', 'deleted_at'], 'notifications_owner_deleted_idx');
                } else {
                    $blueprint->index(['user_id', 'deleted_at'], $table.'_user_deleted_idx');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['notifications', 'savings_goals', 'budgets', 'transactions', 'categories', 'accounts'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropIndex($table === 'notifications' ? 'notifications_owner_deleted_idx' : $table.'_user_deleted_idx');
                $blueprint->dropSoftDeletes();
            });
        }
    }
};
