<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_read_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('connection_id')->nullable()->constrained('chat_connections')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('chat_groups')->cascadeOnDelete();
            // This is a cursor, not a foreign key: expiring its message must not reset the cursor.
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'connection_id']);
            $table->unique(['user_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_read_positions');
    }
};
