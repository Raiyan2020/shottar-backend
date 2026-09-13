<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE notifications MODIFY is_read TINYINT(1) NOT NULL DEFAULT 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE notifications MODIFY is_read TINYINT(1) NOT NULL DEFAULT 1');
    }
};
