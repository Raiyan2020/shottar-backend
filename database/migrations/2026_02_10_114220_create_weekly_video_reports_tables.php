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
        Schema::create('weekly_video_reports_tables', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->dateTime('generated_at');
            $table->string('status')->default('pending'); // pending|sent|failed
            $table->dateTime('sent_at')->nullable();
            $table->longText('message')->nullable();
            $table->timestamps();

            $table->unique(['period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_video_reports_tables');
    }
};
