<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * الشروط وسياسة الخصوصية بالعربي بيتجاوزوا حد TEXT (65,535 بايت) لأن كل حرف
     * عربي بياخد بايتين، فالنص بيتقطع من غير أي خطأ. LONGTEXT بيحل المشكلة.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `settings` MODIFY `value` LONGTEXT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `settings` MODIFY `value` TEXT NULL');
        }
    }
};
