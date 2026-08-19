<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // تقييم المادة (0 - 5) وشارة العرض على الكارت
            $table->decimal('rating', 3, 2)->nullable()->after('price');
            $table->string('tag', 32)->nullable()->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['rating', 'tag']);
        });
    }
};
