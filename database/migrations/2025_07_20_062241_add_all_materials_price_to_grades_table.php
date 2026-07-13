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
            //icon_number
            $table->string('icon_number')->nullable()->after('name_en');
            $table->decimal('all_materials_price', 8, 2)->nullable()->after('name_en');

            $table->integer('order_by')->default(0)->after('all_materials_price');
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
