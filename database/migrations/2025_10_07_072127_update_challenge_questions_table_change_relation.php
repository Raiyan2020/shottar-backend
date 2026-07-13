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
        Schema::table('challenge_questions', function (Blueprint $table) {
            // إزالة العلاقة القديمة
            if (Schema::hasColumn('challenge_questions', 'challenge_lesson_id')) {
                $table->dropForeign(['challenge_lesson_id']);
                $table->dropColumn('challenge_lesson_id');
            }

            // إضافة العلاقة الجديدة مع lesson_sections
            $table->foreignId('lesson_section_id')
                ->after('id')
                ->constrained('lesson_sections')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('challenge_questions', function (Blueprint $table) {
            $table->dropForeign(['lesson_section_id']);
            $table->dropColumn('lesson_section_id');

            // في حال أردت استرجاع العلاقة القديمة
            $table->foreignId('challenge_lesson_id')
                ->constrained()
                ->onDelete('cascade');
        });
    }
};
