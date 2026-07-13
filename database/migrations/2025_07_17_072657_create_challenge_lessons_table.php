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
        Schema::create('challenge_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_material_id')->constrained()->onDelete('cascade');
            $table->integer('order_by')->default(0);
            $table->string('duration')->nullable(); // يمكن تعديله لاحقًا إلى integer للثواني
            $table->boolean('status')->default(1); // 1 = نشط, 0 = غير نشط
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('challenge_lessons');
    }
};
