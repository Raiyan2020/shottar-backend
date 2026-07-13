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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('email')->unique()->nullable();
            $table->enum('status', ['1', '2', '3'])->default('1');
            $table->string('activation_code')->nullable();
            $table->integer('resend_code_count')->default(0);
            $table->text('device_token')->nullable();
            $table->string('device_type')->nullable(); // مثل ios / android

            $table->string('country_code')->nullable();
            $table->string('phone_not_code')->nullable();
            $table->string('language')->default('ar'); // اللغة الافتراضية
            $table->boolean('notification_enabled')->default(true); // تفعيل الإشعارات بشكل افتراضي


            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
