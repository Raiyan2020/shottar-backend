<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // `type` القديم كان بيخلط بين حاجتين مختلفتين: الجمهور المستهدف
            // (all/user/unpaid) ونوع المحتوى. category دلوقتي هو تصنيف
            // المحتوى بس (اللي التبويبات في التطبيق بتفلتر عليه)، مستقل
            // تمامًا عن type. string مش enum عشان لو ضفنا تصنيف جديد بعدين
            // منحتاجش migration تانية تعدّل enum.
            $table->string('category', 32)->default('general')->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
