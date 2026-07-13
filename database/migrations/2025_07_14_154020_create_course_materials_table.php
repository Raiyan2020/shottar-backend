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
        Schema::create('course_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('lesson_section_id')->constrained()->onDelete('cascade');
            $table->string('name_ar');
            $table->string('name_en');
            $table->enum('type', ['lesson', 'note'])->default('lesson'); // نوع المحتوى
            $table->string('duration')->nullable(); // مدة الفيديو أو المذكرة (مثلاً: 20 دقيقة أو 10 صفحات)
            $table->string('duration_text')->nullable(); // نص المدة (مثلاً: 20 دقيقة أو 10 صفحات)
            $table->string('file')->nullable(); // رابط الفيديو أو ملف PDF
            $table->string('video')->nullable(); // رابط الفيديو إذا كان من نوع lesson
            $table->boolean('status')->default(true); // مفعل أو لا
            $table->boolean('is_free')->default(false); // هل المحتوى مجاني أم مدفوع

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_materials');
    }
};
