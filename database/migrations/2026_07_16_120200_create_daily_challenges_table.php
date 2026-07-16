<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->unsignedInteger('reward_points')->default(0);
            $table->unsignedInteger('time_limit_seconds')->default(180);
            $table->date('challenge_date');
            $table->boolean('status')->default(1);
            $table->timestamps();

            $table->unique(['grade_id', 'semester_id', 'challenge_date'], 'daily_challenge_unique_per_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_challenges');
    }
};
