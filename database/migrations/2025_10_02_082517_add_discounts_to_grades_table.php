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
        Schema::table('grades', function (Blueprint $table) {
            $table->decimal('discount_2_materials', 5, 2)->default(10); // 10%
            $table->decimal('discount_3_materials', 5, 2)->default(15); // 15%
            $table->decimal('discount_4_materials', 5, 2)->default(20);
            $table->decimal('discount_5_materials', 5, 2)->default(25);
            $table->decimal('discount_6_materials', 5, 2)->default(30);
            $table->decimal('discount_7_materials', 5, 2)->default(35);
            $table->decimal('discount_all_materials', 5, 2)->default(0); // ممكن تعطيه قيمة افتراضية
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            //
        });
    }
};
