<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semester_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['subject_id', 'semester_id']);
        });

        // نقل الفصل الدراسي الحالي لكل مادة إلى جدول الربط (حتى لا يتغير أي سلوك حالي)
        $now = now();
        DB::table('subjects')
            ->whereNotNull('semester_id')
            ->orderBy('id')
            ->chunkById(200, function ($subjects) use ($now) {
                $rows = [];
                foreach ($subjects as $subject) {
                    $rows[] = [
                        'subject_id'  => $subject->id,
                        'semester_id' => $subject->semester_id,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                }
                if (!empty($rows)) {
                    DB::table('semester_subject')->insertOrIgnore($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('semester_subject');
    }
};
