<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('apple_transaction_id')->nullable()->unique()->after('payment_reference');
            $table->string('apple_original_transaction_id')->nullable()->after('apple_transaction_id');
            $table->string('apple_product_id')->nullable()->after('apple_original_transaction_id');
            $table->string('apple_environment', 32)->nullable()->after('apple_product_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('apple_original_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['apple_transaction_id']);
            $table->dropIndex(['apple_original_transaction_id']);
            $table->dropColumn([
                'apple_transaction_id',
                'apple_original_transaction_id',
                'apple_product_id',
                'apple_environment',
            ]);
        });
    }
};
