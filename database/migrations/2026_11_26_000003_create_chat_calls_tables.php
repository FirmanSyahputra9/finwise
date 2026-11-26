<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_calls', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('connection_id')->constrained('chat_connections')->cascadeOnDelete();
            $table->foreignId('caller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('callee_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 16)->default('ringing');
            $table->timestamp('caller_seen_at')->nullable();
            $table->timestamp('callee_seen_at')->nullable();
            $table->timestamps();
            $table->index(['connection_id', 'status']);
            $table->index(['callee_id', 'status']);
        });

        Schema::create('chat_call_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_call_id')->constrained('chat_calls')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 16);
            $table->longText('payload')->nullable();
            $table->timestamps();
            $table->index(['chat_call_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_call_signals');
        Schema::dropIfExists('chat_calls');
    }
};
