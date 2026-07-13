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
        Schema::table('lesson_sections', function (Blueprint $table) {
            $table->integer('challenge_duration')->nullable()->after('order_by'); // مدة التحدي بالدقائق
            $table->boolean('challenge_active')->default(false)->after('challenge_duration'); // تفعيل أو تعطيل التحدي

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_sections', function (Blueprint $table) {
            //
        });
    }
};
