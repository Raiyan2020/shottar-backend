<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // طلب تغيير رقم الجوال المعلّق (لا يُطبّق إلا بعد تأكيد الـ OTP)
            $table->string('pending_phone')->nullable()->after('phone_not_code');
            $table->string('pending_country_code', 8)->nullable()->after('pending_phone');
            $table->string('pending_phone_code', 8)->nullable()->after('pending_country_code');
            $table->timestamp('pending_phone_expires_at')->nullable()->after('pending_phone_code');
            $table->unsignedTinyInteger('pending_phone_attempts')->default(0)->after('pending_phone_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'pending_phone',
                'pending_country_code',
                'pending_phone_code',
                'pending_phone_expires_at',
                'pending_phone_attempts',
            ]);
        });
    }
};
