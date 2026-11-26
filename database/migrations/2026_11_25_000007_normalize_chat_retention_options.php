<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['chat_connections', 'chat_groups'] as $table) {
            DB::table($table)->select(['id', 'retention_days'])->orderBy('id')->get()->each(function ($conversation) use ($table) {
                $days = (int) $conversation->retention_days;
                if (! in_array($days, [1, 3, 5, 7, 15, 30], true)) {
                    $days = collect([1, 3, 5, 7, 15, 30])->sortBy(fn ($option) => abs($option - $days))->first();
                    DB::table($table)->where('id', $conversation->id)->update(['retention_days' => $days]);
                }
            });
        }
    }

    public function down(): void
    {
        // Retention values are intentionally normalized to the supported options.
    }
};
