<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_challenge_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_challenge_id')->constrained('daily_challenges')->cascadeOnDelete();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->boolean('is_correct')->default(0);
            $table->unsignedInteger('order_by')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_challenge_options');
    }
};
