<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('chat_group_members', function (Blueprint $table) {
            $table->foreignId('chat_group_id')->constrained('chat_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 16)->default('member');
            $table->timestamps();
            $table->primary(['chat_group_id', 'user_id']);
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('connection_id')->constrained('chat_groups')->cascadeOnDelete();
            $table->foreignId('connection_id')->nullable()->change();
            $table->index(['group_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropIndex(['group_id', 'id']);
            $table->dropConstrainedForeignId('group_id');
            $table->foreignId('connection_id')->nullable(false)->change();
        });

        Schema::dropIfExists('chat_group_members');
        Schema::dropIfExists('chat_groups');
    }
};
