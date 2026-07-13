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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();                // كود الكوبون
            $table->enum('type', ['fixed', 'percent']);      // نوع الخصم (ثابت أو نسبة)
            $table->decimal('value', 10, 2);                 // قيمة الخصم
            $table->decimal('max_discount', 10, 2)->nullable(); // أقصى خصم (للكوبونات النسبية)
            $table->integer('usage_limit')->nullable();      // عدد مرات الاستخدام المسموح
            $table->integer('used_count')->default(0);       // عدد مرات الاستخدام الحالي
            $table->dateTime('starts_at')->nullable();       // بداية صلاحية الكوبون
            $table->dateTime('expires_at')->nullable();      // نهاية صلاحية الكوبون
            $table->boolean('status')->default(true);        // نشط/غير نشط
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
