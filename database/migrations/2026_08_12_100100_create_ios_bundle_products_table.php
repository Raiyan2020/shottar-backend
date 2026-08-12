<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ios_bundle_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->string('ios_product_id');
            $table->timestamps();

            $table->unique(['grade_id', 'semester_id']);
            $table->unique('ios_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ios_bundle_products');
    }
};
