<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('chat_messages')->orderBy('id')->chunkById(200, function ($messages) {
            foreach ($messages as $message) {
                DB::table('chat_messages')->where('id', $message->id)->update([
                    'body' => Crypt::encryptString((string) $message->body),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('chat_messages')->orderBy('id')->chunkById(200, function ($messages) {
            foreach ($messages as $message) {
                DB::table('chat_messages')->where('id', $message->id)->update([
                    'body' => Crypt::decryptString((string) $message->body),
                ]);
            }
        });
    }
};
