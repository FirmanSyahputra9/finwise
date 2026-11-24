<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('accounts', 'user_id')) $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            if (! Schema::hasColumn('accounts', 'name')) $table->string('name')->default('Akun');
            if (! Schema::hasColumn('accounts', 'type')) $table->string('type')->default('cash');
            if (! Schema::hasColumn('accounts', 'opening_balance')) $table->bigInteger('opening_balance')->default(0);
            if (! Schema::hasColumn('accounts', 'color')) $table->string('color', 9)->nullable();
        });

        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'user_id')) $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            if (! Schema::hasColumn('categories', 'name')) $table->string('name')->default('Lainnya');
            if (! Schema::hasColumn('categories', 'type')) $table->string('type')->default('expense');
            if (! Schema::hasColumn('categories', 'icon')) $table->string('icon')->nullable();
            if (! Schema::hasColumn('categories', 'color')) $table->string('color', 9)->nullable();
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'user_id')) $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            if (! Schema::hasColumn('transactions', 'account_id')) $table->foreignId('account_id')->nullable()->constrained()->cascadeOnDelete();
            if (! Schema::hasColumn('transactions', 'to_account_id')) $table->foreignId('to_account_id')->nullable()->constrained('accounts')->cascadeOnDelete();
            if (! Schema::hasColumn('transactions', 'category_id')) $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            if (! Schema::hasColumn('transactions', 'type')) $table->string('type')->default('expense');
            if (! Schema::hasColumn('transactions', 'amount')) $table->unsignedBigInteger('amount')->default(0);
            if (! Schema::hasColumn('transactions', 'date')) $table->date('date')->nullable();
            if (! Schema::hasColumn('transactions', 'note')) $table->string('note')->nullable();
        });

        Schema::table('budgets', function (Blueprint $table) {
            if (! Schema::hasColumn('budgets', 'user_id')) $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            if (! Schema::hasColumn('budgets', 'category_id')) $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            if (! Schema::hasColumn('budgets', 'amount')) $table->unsignedBigInteger('amount')->default(0);
            if (! Schema::hasColumn('budgets', 'month')) $table->unsignedTinyInteger('month')->default(1);
            if (! Schema::hasColumn('budgets', 'year')) $table->unsignedSmallInteger('year')->default(2026);
        });
    }

    public function down(): void
    {
        // Repair migration is intentionally non-destructive for existing data.
    }
};
