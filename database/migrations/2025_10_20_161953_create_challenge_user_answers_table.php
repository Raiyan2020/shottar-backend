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
        Schema::create('challenge_user_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_user_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('challenge_question_id')->constrained()->onDelete('cascade');
            $table->foreignId('challenge_answer_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challenge_user_answers');
    }
};
