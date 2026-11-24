<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('transactions', ['user_id', 'date'])) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['user_id', 'date'], 'transactions_user_date_index');
            });
        }

        if (! Schema::hasIndex('budgets', ['user_id', 'category_id', 'month', 'year'], 'unique')) {
            Schema::table('budgets', function (Blueprint $table) {
                $table->unique(['user_id', 'category_id', 'month', 'year'], 'budgets_user_category_month_year_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('transactions', 'transactions_user_date_index')) {
            Schema::table('transactions', fn (Blueprint $table) => $table->dropIndex('transactions_user_date_index'));
        }

        if (Schema::hasIndex('budgets', 'budgets_user_category_month_year_unique')) {
            Schema::table('budgets', fn (Blueprint $table) => $table->dropUnique('budgets_user_category_month_year_unique'));
        }
    }
};
