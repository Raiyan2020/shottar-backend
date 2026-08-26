<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * المرفقات (الامتحانات) كانت بتتربط بالمادة كلها من غير وحدة، فالمدرّس مكنش
 * يقدر يحط ملف تابع لوحدة معيّنة. العمود اختياري عشان كل المرفقات الحالية
 * تفضل شغالة على مستوى المادة زي ما هي.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('lesson_section_id')
                ->nullable()
                ->after('subject_id')
                ->constrained('lesson_sections')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign(['lesson_section_id']);
            $table->dropColumn('lesson_section_id');
        });
    }
};
