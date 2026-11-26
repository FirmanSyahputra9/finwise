<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_connections', function (Blueprint $table) {
            $table->unsignedTinyInteger('retention_days')->default(30);
        });

        Schema::table('chat_groups', function (Blueprint $table) {
            $table->unsignedTinyInteger('retention_days')->default(30);
        });
    }

    public function down(): void
    {
        Schema::table('chat_groups', fn (Blueprint $table) => $table->dropColumn('retention_days'));
        Schema::table('chat_connections', fn (Blueprint $table) => $table->dropColumn('retention_days'));
    }
};
