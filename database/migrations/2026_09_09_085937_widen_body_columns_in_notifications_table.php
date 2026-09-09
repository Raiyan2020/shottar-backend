<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * `body`/`body_en` كانوا VARCHAR(255) — إشعار بنص عربي طويل من لوحة الإدارة
     * كان بيرمي "Data too long for column 'body'" لأن الفورم (NotificationRequest)
     * مش بيحدد أقصى طول، على عكس `title` اللي فعلاً محدود بـ 255 حرف.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `notifications` MODIFY `body` TEXT NULL');
            DB::statement('ALTER TABLE `notifications` MODIFY `body_en` TEXT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `notifications` MODIFY `body` VARCHAR(255) NULL');
            DB::statement('ALTER TABLE `notifications` MODIFY `body_en` VARCHAR(255) NULL');
        }
    }
};
