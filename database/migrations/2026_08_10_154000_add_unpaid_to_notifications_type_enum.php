<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL enum alter for notifications.type
        DB::statement("ALTER TABLE notifications MODIFY type ENUM('all', 'user', 'unpaid') NOT NULL");
    }

    public function down(): void
    {
        DB::table('notifications')->where('type', 'unpaid')->update(['type' => 'all']);
        DB::statement("ALTER TABLE notifications MODIFY type ENUM('all', 'user') NOT NULL");
    }
};
