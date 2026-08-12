<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('payment_methods')->where('slug', 'apple_iap')->exists();
        if ($exists) {
            return;
        }

        DB::table('payment_methods')->insert([
            'name_ar' => 'شراء داخل التطبيق (آبل)',
            'name_en' => 'Apple In-App Purchase',
            'image' => null,
            'slug' => 'apple_iap',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('payment_methods')->where('slug', 'apple_iap')->delete();
    }
};
