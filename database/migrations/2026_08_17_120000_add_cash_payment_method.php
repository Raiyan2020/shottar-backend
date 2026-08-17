<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('payment_methods')->where('slug', 'cash')->exists();

        if ($exists) {
            return;
        }

        DB::table('payment_methods')->insert([
            'name_ar' => 'نقدي',
            'name_en' => 'CASH',
            'image' => null,
            'slug' => 'cash',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('payment_methods')->where('slug', 'cash')->delete();
    }
};
