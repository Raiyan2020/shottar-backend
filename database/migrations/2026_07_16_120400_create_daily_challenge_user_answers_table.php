<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_challenge_user_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_challenge_id')->constrained('daily_challenges')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('daily_challenge_option_id')->nullable()->constrained('daily_challenge_options')->nullOnDelete();
            $table->boolean('is_correct')->default(0);
            $table->unsignedInteger('earned_points')->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['daily_challenge_id', 'user_id'], 'daily_challenge_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_challenge_user_answers');
    }
};
