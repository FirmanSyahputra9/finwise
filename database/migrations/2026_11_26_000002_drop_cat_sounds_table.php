<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cat_sounds');
    }

    public function down(): void
    {
        // The retired table had no user data when this migration was added.
    }
};
